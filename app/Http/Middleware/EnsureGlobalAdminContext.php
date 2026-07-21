<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGlobalAdminContext
{
    private int|string|null $previousTeamId = null;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(0);

        if ($request->user()) {
            $request->user()->unsetRelation('roles');
            $request->user()->unsetRelation('permissions');
        }

        return $next($request);
    }

    /**
     * Terminate the request.
     */
    public function terminate(Request $request, Response $response): void
    {
        unset($request, $response);

        if ($this->previousTeamId !== null) {
            setPermissionsTeamId($this->previousTeamId);
        }
    }
}
