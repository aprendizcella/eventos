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
        $organizer = $request->route('organizer');

        if ($organizer instanceof Organizer) {
            $request->attributes->set('current_organizer', $organizer);

            if ($request->hasSession()) {
                $request->session()->put('current_organizer_id', $organizer->id);
            }
        }

        return $next($request);
    }
}
