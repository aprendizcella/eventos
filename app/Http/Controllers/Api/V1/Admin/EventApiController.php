<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Events\RestoreEventAction;
use App\Actions\Admin\Events\SuspendEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SuspendEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventApiController extends Controller
{
    public function index(): JsonResource
    {
        $events = Event::query()->withoutGlobalScopes()->latest()->paginate();

        return EventResource::collection($events);
    }

    public function suspend(SuspendEventRequest $request, Event $event, SuspendEventAction $suspendAction): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $validated = $request->validated();

        $suspendAction($event, $validated['reason'], $actor);

        return response()->json(['message' => 'Event suspended successfully']);
    }

    public function restore(Request $request, Event $event, RestoreEventAction $restoreAction): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $restoreAction($event, $actor);

        return response()->json(['message' => 'Event restored successfully']);
    }
}
