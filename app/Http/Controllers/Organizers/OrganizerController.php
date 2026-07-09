<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizers;

use App\Actions\Organizers\CreateOrganizerAction;
use App\Actions\Organizers\DeleteOrganizerAction;
use App\Actions\Organizers\UpdateOrganizerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizers\CreateOrganizerRequest;
use App\Http\Requests\Organizers\UpdateOrganizerRequest;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OrganizerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreateOrganizerAction $createAction,
        private readonly UpdateOrganizerAction $updateAction,
        private readonly DeleteOrganizerAction $deleteAction,
    ) {}

    public function index(): View
    {
        $organizers = Organizer::query()->orderBy('name')->paginate(15);

        return view('organizers.index', ['organizers' => $organizers]);
    }

    public function create(): View
    {
        $this->authorize('create', Organizer::class);

        return view('organizers.create');
    }

    public function store(CreateOrganizerRequest $request): RedirectResponse
    {
        $this->authorize('create', Organizer::class);

        $user = $request->user();

        if (!$user instanceof User) {
            abort(403);
        }

        $organizer = ($this->createAction)($request->toDto(), $user);

        return to_route('organizers.show', $organizer)
            ->with('success', 'Organizer created successfully.');
    }

    public function show(Organizer $organizer): View
    {
        $this->authorize('view', $organizer);

        return view('organizers.show', ['organizer' => $organizer]);
    }

    public function edit(Organizer $organizer): View
    {
        $this->authorize('update', $organizer);

        return view('organizers.edit', ['organizer' => $organizer]);
    }

    public function update(UpdateOrganizerRequest $request, Organizer $organizer): RedirectResponse
    {
        $this->authorize('update', $organizer);

        $user = $request->user();

        if (!$user instanceof User) {
            abort(403);
        }

        ($this->updateAction)($organizer, $request->toDto(), $user);

        return to_route('organizers.show', $organizer)
            ->with('success', 'Organizer updated successfully.');
    }

    public function destroy(Request $request, Organizer $organizer): RedirectResponse
    {
        $this->authorize('delete', $organizer);

        $user = $request->user();

        if (!$user instanceof User) {
            abort(403);
        }

        ($this->deleteAction)($organizer, $user);

        return to_route('organizers.index')
            ->with('success', 'Organizer deleted successfully.');
    }

    public function dashboard(Organizer $organizer): View
    {
        $this->authorize('view', $organizer);

        return view('organizers.dashboard', ['organizer' => $organizer]);
    }

    public function settings(Organizer $organizer): View
    {
        $this->authorize('update', $organizer);

        return view('organizers.settings', ['organizer' => $organizer]);
    }

    public function reportsBilling(Organizer $organizer): View
    {
        $this->authorize('view', $organizer);

        return view('organizers.reports.billing', ['organizer' => $organizer]);
    }

    public function reportsPayouts(Organizer $organizer): View
    {
        $this->authorize('view', $organizer);

        return view('organizers.reports.payouts', ['organizer' => $organizer]);
    }
}
