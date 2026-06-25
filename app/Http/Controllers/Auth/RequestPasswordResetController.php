<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RequestPasswordResetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestPasswordResetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class RequestPasswordResetController extends Controller
{
    public function __construct(private readonly RequestPasswordResetAction $requestPasswordReset) {}

    public function __invoke(RequestPasswordResetRequest $request): RedirectResponse
    {
        $status = ($this->requestPasswordReset)($request->toDto());

        if ($status === Password::RESET_LINK_SENT) {
            return redirect('/')->with('status', $status);
        }

        // Keep the user on the forgot-password form so the error surfaces there
        // (and the email input is preserved), instead of redirecting to "/".
        return redirect()
            ->route('forgot-password')
            ->withErrors(['email' => __($status)])
            ->withInput(['email' => $request->string('email')->toString()]);
    }
}
