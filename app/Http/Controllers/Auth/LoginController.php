<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    public function __construct(private readonly LoginUserAction $loginUser) {}

    public function __invoke(LoginUserRequest $request): RedirectResponse
    {
        $succeeded = ($this->loginUser)($request->toDto());

        if (!$succeeded) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect('/');
    }
}
