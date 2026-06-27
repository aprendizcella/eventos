<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organizers;

use App\Actions\Organizers\AddTeamMemberAction;
use App\Actions\Organizers\ChangeTeamMemberRoleAction;
use App\Actions\Organizers\RemoveTeamMemberAction;
use App\Exceptions\LastAdminCannotBeRemovedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizers\AddTeamMemberRequest;
use App\Http\Requests\Organizers\ChangeTeamMemberRoleRequest;
use App\Models\Organizer;
use App\Models\User;
use App\Support\Organizers\UniqueConstraintViolationClassifier;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TeamController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AddTeamMemberAction $addAction,
        private readonly RemoveTeamMemberAction $removeAction,
        private readonly ChangeTeamMemberRoleAction $changeRoleAction,
    ) {}

    public function index(Organizer $organizer): View
    {
        $this->authorize('view', $organizer);

        $members = $organizer->users;

        return view('organizers.team.index', ['organizer' => $organizer, 'members' => $members]);
    }

    public function store(AddTeamMemberRequest $request, Organizer $organizer): RedirectResponse
    {
        $this->authorize('manageTeam', $organizer);

        $user = $request->user();

        if (!$user instanceof User) {
            abort(403);
        }

        try {
            ($this->addAction)($organizer, $request->toDto(), $user);
        } catch (QueryException $e) {
            if (UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id', 'user_id'])) {
                throw ValidationException::withMessages([
                    'user_id' => 'This user is already a member of this organizer.',
                ]);
            }

            throw $e;
        }

        return to_route('organizers.team.index', $organizer)
            ->with('success', 'Team member added successfully.');
    }

    public function update(ChangeTeamMemberRoleRequest $request, Organizer $organizer, User $user): RedirectResponse
    {
        $this->authorize('manageTeam', $organizer);

        $changedBy = $request->user();
        $dto = $request->toDto();

        if (!$changedBy instanceof User) {
            abort(403);
        }

        if ($user->getKey() !== $dto->userId) {
            abort(404);
        }

        try {
            ($this->changeRoleAction)($organizer, $dto, $changedBy);
        } catch (LastAdminCannotBeRemovedException $e) {
            throw ValidationException::withMessages([
                'role' => $e->getMessage(),
            ]);
        }

        return to_route('organizers.team.index', $organizer)
            ->with('success', 'Team member role updated successfully.');
    }

    public function destroy(Request $request, Organizer $organizer, User $user): RedirectResponse
    {
        $this->authorize('manageTeam', $organizer);

        $removedBy = $request->user();

        if (!$removedBy instanceof User) {
            abort(403);
        }

        try {
            ($this->removeAction)($organizer, $user, $removedBy);
        } catch (LastAdminCannotBeRemovedException $e) {
            throw ValidationException::withMessages([
                'user_id' => $e->getMessage(),
            ]);
        }

        return to_route('organizers.team.index', $organizer)
            ->with('success', 'Team member removed successfully.');
    }
}
