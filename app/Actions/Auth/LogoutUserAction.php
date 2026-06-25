<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;

final readonly class LogoutUserAction
{
    public function __construct(
        private StatefulGuard $guard,
        private RecordAuthActivityAction $recordAuthActivity,
    ) {}

    public function __invoke(): void
    {
        $user = $this->guard->user();
        $subject = $user instanceof User ? $user : null;
        $causerId = $subject?->id;

        $this->guard->logout();

        ($this->recordAuthActivity)(
            event: 'logout',
            subject: $subject,
            causerId: $causerId,
            context: ['outcome' => 'success'],
        );
    }
}
