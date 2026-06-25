<?php

declare(strict_types=1);

namespace Tests\Auth;

use Illuminate\Support\Facades\Route;

/**
 * Registers the Squoia 2 backend auth routes inside the test router so feature
 * tests can exercise Controllers without touching routes/web.php (formal named
 * routes + throttle middleware land in the Volt UI slice).
 *
 * Controller classes are referenced as string literals on purpose: this keeps
 * the helper loadable during the RED phase before the controllers exist.
 */
final class AuthRouteRegistrar
{
    /** @var list<array{0:string, 1:string, 2:string}> */
    private const array ROUTES = [
        ['POST', 'register', \App\Http\Controllers\Auth\RegisterController::class],
        ['POST', 'login', \App\Http\Controllers\Auth\LoginController::class],
        ['POST', 'logout', \App\Http\Controllers\Auth\LogoutController::class],
        ['POST', 'forgot-password', \App\Http\Controllers\Auth\RequestPasswordResetController::class],
        ['POST', 'reset-password', \App\Http\Controllers\Auth\ResetPasswordController::class],
    ];

    public static function register(): void
    {
        foreach (self::ROUTES as [$method, $uri, $controller]) {
            if (self::has($method, $uri)) {
                continue;
            }

            Route::match([$method], $uri, $controller)->middleware('web');
        }
    }

    private static function has(string $method, string $uri): bool
    {
        $routes = Route::getRoutes();

        return collect($routes->get($method) ?? [])
            ->contains(static fn ($route): bool => $route->uri() === $uri);
    }
}
