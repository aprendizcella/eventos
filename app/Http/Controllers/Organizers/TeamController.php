<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizers;

use App\Actions\Organizers\AddTeamMemberAction;
use App\Actions\Organizers\ChangeTeamMemberRoleAction;
use App\Actions\Organizers\RemoveTeamMemberAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizers\AddTeamMemberRequest;
use App\Http\Requests\Organizers\ChangeTeamMemberRoleRequest;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TeamController extends Controller
{
    public function __construct(
        private readonly AddTeamMemberAction $addAction,
        private readonly RemoveTeamMemberAction $removeAction,
        private readonly ChangeTeamMemberRoleAction $changeRoleAction,
    ) {}

    public function index(Organizer $organizer): View
    {
        $members = $organizer->users;

        return view('organizers.team.index', compact('organizer', 'members'));
    }

    public function store(AddTeamMemberRequest $request, Organizer $organizer): RedirectResponse
    {
        ($this->addAction)($organizer, $request->toDto(), $request->user());

        return redirect()
            ->route('organizers.team.index', $organizer)
            ->with('success', 'Team member added successfully.');
    }

    public function update(ChangeTeamMemberRoleRequest $request, Organizer $organizer, User $user): RedirectResponse
    {
        ($this->changeRoleAction)($organizer, $request->toDto(), $request->user());

        return redirect()
            ->route('organizers.team.index', $organizer)
            ->with('success', 'Team member role updated successfully.');
    }

    public function destroy(Request $request, Organizer $organizer, User $user): RedirectResponse
    {
        ($this->removeAction)($organizer, $user, $request->user());

        return redirect()
            ->route('organizers.team.index', $organizer)
            ->with('success', 'Team member removed successfully.');
    }
}
