<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LogoutUserAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function __construct(private readonly LogoutUserAction $logoutUser) {}

    public function __invoke(Request $request): RedirectResponse
    {
        ($this->logoutUser)();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
