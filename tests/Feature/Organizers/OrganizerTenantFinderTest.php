<?php

declare(strict_types=1);

use App\Http\Middleware\DetectCurrentOrganizer;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Multitenancy\OrganizerTenantFinder;
use App\Support\Organizers\OrganizerRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    resolve(Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
    Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

/**
 * Set route parameters on an unbound Route instance via reflection.
 */
function bindRouteParameter(Route $route, string $name, mixed $value): void
{
    $ref = new ReflectionProperty(Route::class, 'parameters');
    $ref->setValue($route, [$name => $value]);
}

// --------------------------------------------------------------------------
// Task 2.1 — Finder unit tests
// --------------------------------------------------------------------------

it('returns null from finder when host matches APP_URL and no route organizer', function (): void {
    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    $request = Request::create("http://{$appUrlHost}/dashboard", 'GET');

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeNull();
});

it('returns organizer from finder when host matches custom domain', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Finder Host Org',
        'slug' => 'finder-host-org',
        'domain' => 'finder-host.example.com',
    ]);

    $request = Request::create('http://finder-host.example.com/', 'GET');

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeInstanceOf(Organizer::class);
    expect($tenant?->getKey())->toBe($organizer->getKey());
});

it('returns null from finder when no organizer domain matches and no route', function (): void {
    $request = Request::create('http://no-match.example.com/', 'GET');

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeNull();
});

it('resolves route organizer from finder when route is bound', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Finder Route Test',
        'slug' => 'finder-route-test',
    ]);

    $request = Request::create("http://localhost/organizers/{$organizer->id}/dashboard", 'GET');

    $route = new Route(['GET'], '/organizers/{organizer}/dashboard', []);
    bindRouteParameter($route, 'organizer', $organizer);
    $request->setRouteResolver(fn () => $route);

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeInstanceOf(Organizer::class);
    expect($tenant?->getKey())->toBe($organizer->getKey());
});

it('returns null from finder when route is not bound yet', function (): void {
    $request = Request::create('http://localhost/organizers/1/dashboard', 'GET');
    // No route resolver set — simulates boot phase where route is not matched

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeNull();
});

// --------------------------------------------------------------------------
// Task 2.1 — Finder precedence: host wins over route
// --------------------------------------------------------------------------

it('resolves by host from finder even when route has a different organizer', function (): void {
    $hostOrg = Organizer::query()->create([
        'name' => 'Host Wins',
        'slug' => 'host-wins',
        'domain' => 'host-wins.example.com',
    ]);

    $routeOrg = Organizer::query()->create([
        'name' => 'Route Loser',
        'slug' => 'route-loser',
    ]);

    $request = Request::create("http://host-wins.example.com/organizers/{$routeOrg->id}", 'GET');
    $route = new Route(['GET'], '/organizers/{organizer}', []);
    bindRouteParameter($route, 'organizer', $routeOrg);
    $request->setRouteResolver(fn () => $route);

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    // Host match should win over route parameter
    expect($tenant)->toBeInstanceOf(Organizer::class);
    expect($tenant?->getKey())->toBe($hostOrg->getKey());
});

it('resolves via route fallback from finder when host matches APP_URL but route has organizer', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Route On Root',
        'slug' => 'route-on-root',
    ]);

    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $request = Request::create("http://{$appUrlHost}/organizers/{$organizer->id}/dashboard", 'GET');
    $route = new Route(['GET'], '/organizers/{organizer}/dashboard', []);
    bindRouteParameter($route, 'organizer', $organizer);
    $request->setRouteResolver(fn () => $route);

    $tenant = (new OrganizerTenantFinder)->findForRequest($request);

    expect($tenant)->toBeInstanceOf(Organizer::class);
    expect($tenant?->getKey())->toBe($organizer->getKey());
});

// --------------------------------------------------------------------------
// Task 2.2 — DetectCurrentOrganizer middleware integration
// --------------------------------------------------------------------------

it('skips setting tenant when a tenant is already current (from host resolution)', function (): void {
    $hostOrganizer = Organizer::query()->create([
        'name' => 'Already Current',
        'slug' => 'already-current',
    ]);

    // Simulate the finder having already resolved this tenant
    $hostOrganizer->makeCurrent();
    expect(Organizer::checkCurrent())->toBeTrue();

    $routeOrganizer = Organizer::query()->create([
        'name' => 'Should Not Override',
        'slug' => 'should-not-override',
    ]);

    $request = Request::create(
        "http://localhost/organizers/{$routeOrganizer->id}/dashboard",
        'GET',
    );
    $route = new Route(['GET'], '/organizers/{organizer}/dashboard', []);
    bindRouteParameter($route, 'organizer', $routeOrganizer);
    $request->setRouteResolver(fn () => $route);

    $middleware = new DetectCurrentOrganizer;
    $middleware->handle($request, fn ($req) => new Response);

    // Should NOT have been overridden
    expect(Organizer::checkCurrent())->toBeTrue();
    expect(Organizer::current()?->getKey())->toBe($hostOrganizer->getKey());
    expect($request->attributes->get('current_organizer'))->toBeNull();
});

it('makes route organizer current when no tenant is resolved', function (): void {
    $organizer = Organizer::query()->create([
        'name' => 'Middleware Makes Current',
        'slug' => 'middleware-makes-current',
    ]);

    expect(Organizer::checkCurrent())->toBeFalse();

    $request = Request::create(
        "http://localhost/organizers/{$organizer->id}/dashboard",
        'GET',
    );
    $route = new Route(['GET'], '/organizers/{organizer}/dashboard', []);
    bindRouteParameter($route, 'organizer', $organizer);
    $request->setRouteResolver(fn () => $route);

    $middleware = new DetectCurrentOrganizer;
    $middleware->handle($request, fn ($req) => new Response);

    expect(Organizer::checkCurrent())->toBeTrue();
    expect(Organizer::current()?->getKey())->toBe($organizer->getKey());
    expect($request->attributes->get('current_organizer'))->toBe($organizer);
});

it('passes through middleware when no organizer in route', function (): void {
    $request = Request::create('/dashboard', 'GET');

    $middleware = new DetectCurrentOrganizer;
    $response = $middleware->handle($request, fn ($req) => new Response);

    expect($request->attributes->get('current_organizer'))->toBeNull();
    expect(Organizer::checkCurrent())->toBeFalse();
});

// --------------------------------------------------------------------------
// Task 2.3 — Route fallback integration via full HTTP stack
// --------------------------------------------------------------------------

it('keeps APP_URL root domain tenant-less on welcome page', function (): void {
    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->get("http://{$appUrlHost}/")
        ->assertSuccessful();

    expect(Organizer::checkCurrent())->toBeFalse();
});

it('keeps login page tenant-less on APP_URL host', function (): void {
    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->get("http://{$appUrlHost}/login")
        ->assertSuccessful();

    expect(Organizer::checkCurrent())->toBeFalse();
});

it('resolves tenant by route fallback via middleware on authenticated organizer request', function (): void {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create();
    $organizer->users()->attach($user, ['role' => OrganizerRoles::Admin->value]);

    $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $response = $this->actingAs($user)
        ->get("http://{$appUrlHost}/organizers/{$organizer->id}/dashboard");

    $response->assertOk();
    expect(Organizer::checkCurrent())->toBeTrue();
    expect(Organizer::current()?->getKey())->toBe($organizer->getKey());
});

// --------------------------------------------------------------------------
// Task 2.3 — Cross-organizer isolation
// --------------------------------------------------------------------------

it('isolates tenant state when switching between organizers', function (): void {
    $orgA = Organizer::query()->create(['name' => 'Org A', 'slug' => 'org-a', 'domain' => 'org-a.example.com']);
    $orgB = Organizer::query()->create(['name' => 'Org B', 'slug' => 'org-b', 'domain' => 'org-b.example.com']);

    expect(Organizer::checkCurrent())->toBeFalse();

    $orgA->makeCurrent();
    expect(Organizer::current()?->getKey())->toBe($orgA->getKey());
    expect($orgA->isCurrent())->toBeTrue();
    expect($orgB->isCurrent())->toBeFalse();

    Organizer::forgetCurrent();

    $orgB->makeCurrent();
    expect(Organizer::current()?->getKey())->toBe($orgB->getKey());
    expect($orgB->isCurrent())->toBeTrue();
    expect($orgA->isCurrent())->toBeFalse();
});
