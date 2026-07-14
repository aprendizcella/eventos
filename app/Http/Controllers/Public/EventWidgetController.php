<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EventWidgetController
{
    public function __invoke(Request $request): JsonResponse
    {
        $organizerSlug = $request->string('organizer');

        if ($organizerSlug->isEmpty()) {
            return response()->json(['error' => 'Organizer parameter is required.'], 400);
        }

        $limit = $request->integer('limit', 5);

        if ($limit < 1 || $limit > 20) {
            return response()->json(['error' => 'Limit must be between 1 and 20.'], 422);
        }

        $organizer = Organizer::query()->where('slug', $organizerSlug)->first();

        if ($organizer === null) {
            return response()->json(['error' => 'Organizer not found.'], 404);
        }

        $events = Event::query()
            ->where('organizer_id', $organizer->id)
            ->where('status', EventStatus::Published->value)
            ->where('visibility', EventVisibility::Public->value)
            ->oldest('starts_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'organizer' => [
                'name' => $organizer->name,
            ],
            'events' => $events->map(fn (Event $event): array => [
                'title' => $event->title,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'url' => route('public.events.detail', $event->slug),
            ]),
        ])->header('Access-Control-Allow-Origin', '*');
    }
}
