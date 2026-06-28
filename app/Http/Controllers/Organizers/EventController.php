<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizers;

use App\Actions\Events\CancelEventAction;
use App\Actions\Events\CreateEventAction;
use App\Actions\Events\PauseEventAction;
use App\Actions\Events\PublishEventAction;
use App\Actions\Events\UpdateEventAction;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EventController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreateEventAction $createAction,
        private readonly UpdateEventAction $updateAction,
        private readonly PublishEventAction $publishAction,
        private readonly PauseEventAction $pauseAction,
        private readonly CancelEventAction $cancelAction,
    ) {}

    public function index(Request $request, Organizer $organizer): View
    {
        $this->authorize('viewAny', [Event::class, $organizer]);

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $visibility = (string) $request->query('visibility', '');
        $startsFrom = (string) $request->query('starts_from', '');
        $startsUntil = (string) $request->query('starts_until', '');

        $query = $organizer->events()->latest('starts_at');

        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($status !== '' && EventStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        if ($visibility !== '' && EventVisibility::tryFrom($visibility) !== null) {
            $query->where('visibility', $visibility);
        }

        if ($startsFrom !== '') {
            $query->where('starts_at', '>=', $startsFrom);
        }

        if ($startsUntil !== '') {
            $query->where('starts_at', '<=', $startsUntil.' 23:59:59');
        }

        $events = $query->paginate(15)->withQueryString();

        $filters = [
            'search' => $search,
            'status' => $status,
            'visibility' => $visibility,
            'starts_from' => $startsFrom,
            'starts_until' => $startsUntil,
        ];

        $statusOptions = ['' => 'All statuses'] + collect(EventStatus::cases())
            ->mapWithKeys(fn (EventStatus $s) => [$s->value => ucfirst($s->value)])
            ->all();

        $visibilityOptions = ['' => 'All visibilities'] + collect(EventVisibility::cases())
            ->mapWithKeys(fn (EventVisibility $v) => [$v->value => ucfirst($v->value)])
            ->all();

        return view('organizers.events.index', [
            'organizer' => $organizer,
            'events' => $events,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'visibilityOptions' => $visibilityOptions,
        ]);
    }

    public function create(Organizer $organizer): View
    {
        $this->authorize('create', [Event::class, $organizer]);

        return view('organizers.events.create', [
            'organizer' => $organizer,
            'categories' => Category::query()->orderBy('name')->get(),
            'venues' => $organizer->venues()->orderBy('name')->get(),
            'visibilityOptions' => collect(EventVisibility::cases())
                ->mapWithKeys(fn (EventVisibility $v) => [$v->value => ucfirst($v->value)])
                ->all(),
        ]);
    }

    public function store(CreateEventRequest $request, Organizer $organizer): RedirectResponse
    {
        $this->authorize('create', [Event::class, $organizer]);

        $event = ($this->createAction)($request->toDto($organizer->getKey()));

        return to_route('organizers.events.show', [$organizer, $event])
            ->with('success', 'Event created successfully.');
    }

    public function show(Organizer $organizer, Event $event): View
    {
        $this->authorize('view', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        return view('organizers.events.show', [
            'organizer' => $organizer,
            'event' => $event,
        ]);
    }

    public function edit(Organizer $organizer, Event $event): View
    {
        $this->authorize('update', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        return view('organizers.events.edit', [
            'organizer' => $organizer,
            'event' => $event,
            'categories' => Category::query()->orderBy('name')->get(),
            'venues' => $organizer->venues()->orderBy('name')->get(),
            'visibilityOptions' => collect(EventVisibility::cases())
                ->mapWithKeys(fn (EventVisibility $v) => [$v->value => ucfirst($v->value)])
                ->all(),
        ]);
    }

    public function update(UpdateEventRequest $request, Organizer $organizer, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        ($this->updateAction)($event, $request->toDto());

        return to_route('organizers.events.show', [$organizer, $event])
            ->with('success', 'Event updated successfully.');
    }

    public function publish(Organizer $organizer, Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        ($this->publishAction)($event);

        return to_route('organizers.events.show', [$organizer, $event])
            ->with('success', 'Event published successfully.');
    }

    public function pause(Organizer $organizer, Event $event): RedirectResponse
    {
        $this->authorize('pause', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        ($this->pauseAction)($event);

        return to_route('organizers.events.show', [$organizer, $event])
            ->with('success', 'Event paused successfully.');
    }

    public function cancel(Organizer $organizer, Event $event): RedirectResponse
    {
        $this->authorize('cancel', $event);

        $this->ensureEventBelongsToOrganizer($event, $organizer);

        ($this->cancelAction)($event);

        return to_route('organizers.events.show', [$organizer, $event])
            ->with('success', 'Event cancelled successfully.');
    }

    private function ensureEventBelongsToOrganizer(Event $event, Organizer $organizer): void
    {
        if ($event->organizer_id !== $organizer->getKey()) {
            abort(404);
        }
    }
}
