<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\SendBulkMessageAction;
use App\Actions\Tickets\CheckInAttendeeAction;
use App\DataTransferObjects\Notifications\SendBulkMessageDto;
use App\Exceptions\Tickets\CheckInException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiCheckInRequest;
use App\Http\Requests\SendBulkMessageRequest;
use App\Http\Resources\AttendeeApiResource;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class EventApiController extends Controller
{
    /**
     * GET /api/v1/events/{event}/attendees
     */
    public function attendees(Event $event): AnonymousResourceCollection
    {
        $this->authorizeOrganizerMember($event);
        Gate::authorize('viewCheckIn', $event);

        $attendees = Attendee::query()
            ->select([
                'attendee.*',
                DB::raw('EXISTS(
                    SELECT 1 FROM active_check_in
                    WHERE active_check_in.attendee_id = attendee.attendee_id
                ) as is_checked_in'),
            ])
            ->join('ticket_order', 'attendee.ticket_order_id', '=', 'ticket_order.ticket_order_id')
            ->where('ticket_order.event_id', $event->event_id)
            ->paginate(50);

        return AttendeeApiResource::collection($attendees);
    }

    /**
     * POST /api/v1/events/{event}/check-in
     *
     * @throws ValidationException
     */
    public function checkIn(ApiCheckInRequest $request, Event $event, CheckInAttendeeAction $action): JsonResponse
    {
        $this->authorizeOrganizerMember($event);
        Gate::authorize('checkIn', $event);

        $validated = $request->validated();

        // Si no se pasa lista, buscar la lista activa por defecto para el evento
        $listId = $validated['check_in_list_id'] ?? null;

        if ($listId === null) {
            $defaultList = CheckInList::query()
                ->where('event_id', $event->event_id)
                ->where('is_active', true)
                ->first();

            if ($defaultList === null) {
                return response()->json([
                    'message' => __('No active check-in list found for this event.'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $listId = $defaultList->check_in_list_id;
        }

        try {
            $action($validated['unique_code'], (int) $listId, (int) auth()->id());

            return response()->json([
                'message' => __('Check-in registered successfully.'),
            ], Response::HTTP_OK);
        } catch (CheckInException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /api/v1/events/{event}/messages
     */
    public function messages(SendBulkMessageRequest $request, Event $event, SendBulkMessageAction $action): JsonResponse
    {
        $this->authorizeOrganizerMember($event);
        Gate::authorize('sendMessages', $event);

        $dto = new SendBulkMessageDto(
            eventId: $event->event_id,
            subject: $request->input('subject'),
            body: $request->input('body'),
            productPriceId: $request->input('product_price_id') ? (int) $request->input('product_price_id') : null,
            attendeeStatus: $request->input('attendee_status'),
            checkInStatus: $request->input('check_in_status'),
        );

        $action($dto, (int) auth()->id());

        return response()->json([
            'message' => __('Bulk email campaign queued successfully.'),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Valida que el usuario tenga acceso al organizador del evento para asegurar multi-tenancy.
     */
    private function authorizeOrganizerMember(Event $event): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        // Si es super/platform admin tiene paso total
        if ($user->hasRole(['super_admin', 'platform_admin'])) {
            return;
        }

        $isMember = $user->organizers()
            ->where('organizers.id', $event->organizer_id)
            ->exists();

        if (!$isMember) {
            abort(Response::HTTP_FORBIDDEN, __('You do not belong to the organizer of this event.'));
        }
    }
}
