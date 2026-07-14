<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class EventRedirectController
{
    public function __invoke(int $id): RedirectResponse
    {
        $event = Event::query()->find($id);

        if ($event === null || $event->visibility !== EventVisibility::Public || $event->status !== EventStatus::Published) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return to_route('public.events.detail', $event->slug, Response::HTTP_MOVED_PERMANENTLY);
    }
}
