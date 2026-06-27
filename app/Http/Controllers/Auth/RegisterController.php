<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;

final class RegisterController extends Controller
{
    public function __construct(private readonly RegisterUserAction $registerUser) {}

    public function __invoke(RegisterUserRequest $request): RedirectResponse
    {
        ($this->registerUser)($request->toDto());

        return redirect()->route('verification.notice');
    }
}
