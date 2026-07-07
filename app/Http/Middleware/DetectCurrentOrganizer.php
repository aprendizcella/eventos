<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectCurrentOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        // If the multitenancy finder already resolved a tenant by host,
        // do NOT override it — keep the transition guard.
        if (Organizer::checkCurrent()) {
            return $next($request);
        }

        $route = $request->route();

        $organizer = null;

        if ($route !== null && $route->hasParameter('organizer')) {
            $organizer = $route->parameter('organizer');
        }

        if ($organizer instanceof Organizer) {
            // Route fallback: make the route-bound organizer the current tenant
            // so the Spatie package's current() / checkCurrent() works consistently.
            $organizer->makeCurrent();

            $request->attributes->set('current_organizer', $organizer);

            if ($request->hasSession()) {
                $request->session()->put('current_organizer_id', $organizer->id);
            }
        }

        return $next($request);
    }
}
