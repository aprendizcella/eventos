<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Users\RestoreUserAction;
use App\Actions\Admin\Users\SuspendUserAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserApiController extends Controller
{
    public function index(): JsonResource
    {
        $users = User::query()->latest()->paginate();

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResource
    {
        return new UserResource($user);
    }

    public function suspend(Request $request, User $user, SuspendUserAction $suspendAction): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        if (!$actor->hasRole('super_admin')) {
            abort(403, 'Only super administrators can suspend users.');
        }

        $suspendAction($user);

        return response()->json(['message' => 'User suspended successfully']);
    }

    public function restore(Request $request, User $user, RestoreUserAction $restoreAction): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        if (!$actor->hasRole('super_admin')) {
            abort(403, 'Only super administrators can restore users.');
        }

        $restoreAction($user);

        return response()->json(['message' => 'User restored successfully']);
    }
}
