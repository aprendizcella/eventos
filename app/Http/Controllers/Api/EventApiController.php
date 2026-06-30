<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class EventApiController extends Controller
{
    use AuthorizesRequests;

    public function index(Organizer $organizer): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Event::class, $organizer]);

        $events = $organizer->events()->paginate(15);

        return EventResource::collection($events);
    }
}
