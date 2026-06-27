<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\UpdatePasswordAction;
use App\Actions\Account\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Http\Requests\Account\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function editProfile(): View
    {
        return view('account.profile');
    }

    public function updateProfile(UpdateProfileRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $action($user, $request->name());

        return to_route('account.profile.edit')
            ->with('status', __('Profile updated successfully.'));
    }

    public function editPassword(): View
    {
        return view('account.password');
    }

    public function updatePassword(UpdatePasswordRequest $request, UpdatePasswordAction $action): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $action($user, $request->newPassword());

        return to_route('account.password.edit')
            ->with('status', __('Password updated successfully.'));
    }
}
