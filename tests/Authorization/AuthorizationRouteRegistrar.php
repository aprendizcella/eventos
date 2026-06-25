<?php

declare(strict_types=1);

namespace Tests\Authorization;

use Illuminate\Support\Facades\Route;

/**
 * Registers a representative role-protected test route so authorization
 * feature tests can exercise the Spatie `role` middleware alias registered in
 * bootstrap/app.php without depending on future organizer/event domain routes.
 */
final class AuthorizationRouteRegistrar
{
    public static function register(): void
    {
        if (self::has('GET', 'role-protected-test')) {
            return;
        }

        Route::get('role-protected-test', static fn () => response('ok'))
            ->middleware(['web', 'role:super_admin']);
    }

    private static function has(string $method, string $uri): bool
    {
        $routes = Route::getRoutes();

        return collect($routes->get($method) ?? [])
            ->contains(static fn ($route): bool => $route->uri() === $uri);
    }
}
