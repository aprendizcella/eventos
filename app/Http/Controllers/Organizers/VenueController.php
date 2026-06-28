<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizers;

use App\Actions\Venues\CreateVenueAction;
use App\Actions\Venues\UpdateVenueAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\CreateVenueRequest;
use App\Http\Requests\Venues\UpdateVenueRequest;
use App\Models\Organizer;
use App\Models\Venue;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class VenueController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreateVenueAction $createAction,
        private readonly UpdateVenueAction $updateAction,
    ) {}

    public function index(Organizer $organizer): View
    {
        $this->authorize('viewAny', [Venue::class, $organizer]);

        $venues = $organizer->venues()->orderBy('name')->paginate(15);

        return view('organizers.venues.index', [
            'organizer' => $organizer,
            'venues' => $venues,
        ]);
    }

    public function create(Organizer $organizer): View
    {
        $this->authorize('create', [Venue::class, $organizer]);

        return view('organizers.venues.create', [
            'organizer' => $organizer,
        ]);
    }

    public function store(CreateVenueRequest $request, Organizer $organizer): RedirectResponse
    {
        $this->authorize('create', [Venue::class, $organizer]);

        ($this->createAction)($request->toDto($organizer->getKey()));

        return to_route('organizers.venues.index', $organizer)
            ->with('success', 'Venue created successfully.');
    }

    public function edit(Organizer $organizer, Venue $venue): View
    {
        $this->authorize('update', $venue);

        $this->ensureVenueBelongsToOrganizer($venue, $organizer);

        return view('organizers.venues.edit', [
            'organizer' => $organizer,
            'venue' => $venue,
        ]);
    }

    public function update(UpdateVenueRequest $request, Organizer $organizer, Venue $venue): RedirectResponse
    {
        $this->authorize('update', $venue);

        $this->ensureVenueBelongsToOrganizer($venue, $organizer);

        ($this->updateAction)($venue, $request->toDto());

        return to_route('organizers.venues.index', $organizer)
            ->with('success', 'Venue updated successfully.');
    }

    private function ensureVenueBelongsToOrganizer(Venue $venue, Organizer $organizer): void
    {
        if ($venue->organizer_id !== $organizer->getKey()) {
            abort(404);
        }
    }
}
