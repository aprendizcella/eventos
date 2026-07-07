<?php

declare(strict_types=1);

namespace App\Support\Multitenancy;

use App\Models\Organizer;
use Illuminate\Http\Request;
use Override;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

final class OrganizerTenantFinder extends TenantFinder
{
    /**
     * Resolve the current tenant by host-first strategy with route fallback
     * for internal organizer panel URLs.
     *
     * Precedence:
     * 1. Root domain (APP_URL host) → route fallback or null (superadmin context)
     * 2. Custom organizer domain → return that organizer
     * 3. Route {organizer} parameter → return route-bound organizer (fallback)
     * 4. No match → null (global context, no tenant)
     */
    #[Override]
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();
        $appUrlHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        // 1. Root domain (APP_URL) — superadmin context, only route fallback applies
        if ($host === $appUrlHost) {
            return $this->resolveFromRoute($request);
        }

        // 2. Host match with an organizer's custom domain
        $organizer = Organizer::query()->where('domain', $host)->first();

        if ($organizer instanceof Organizer) {
            return $organizer;
        }

        // 3. Route fallback (only works after routing has matched, i.e. during middleware phase)
        return $this->resolveFromRoute($request);
    }

    /**
     * Attempt to resolve an organizer from the route {organizer} parameter.
     *
     * The route may not be bound yet when this runs during service provider boot,
     * so we guard against the unbound state gracefully.
     */
    private function resolveFromRoute(Request $request): ?IsTenant
    {
        $route = $request->route();

        if ($route === null || !$route->hasParameter('organizer')) {
            return null;
        }

        $organizer = $route->parameter('organizer');

        return $organizer instanceof Organizer ? $organizer : null;
    }
}
