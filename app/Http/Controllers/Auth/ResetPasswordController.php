<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class ResetPasswordController extends Controller
{
    public function __construct(private readonly ResetPasswordAction $resetPassword) {}

    public function __invoke(ResetPasswordRequest $request): RedirectResponse
    {
        $status = ($this->resetPassword)($request->toDto());

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/')->with('status', $status);
        }

        // Keep the user on the reset-password form so the error surfaces there
        // (and the email input + token are preserved), instead of redirecting
        // to "/". The token is replayed so the form can submit again.
        return redirect()
            ->route('password.reset', ['token' => $request->string('token')->toString()])
            ->withErrors(['email' => __($status)])
            ->withInput(['email' => $request->string('email')->toString()]);
    }
}
