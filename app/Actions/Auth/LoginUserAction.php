<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\LoginUserDto;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;

final readonly class LoginUserAction
{
    public function __construct(
        private StatefulGuard $guard,
        private RecordAuthActivityAction $recordAuthActivity,
    ) {}

    public function __invoke(LoginUserDto $dto): bool
    {
        $succeeded = $this->guard->attempt([
            'email' => $dto->email,
            'password' => $dto->password,
            fn ($query) => $query->whereNull('suspended_at'),
        ], $dto->remember);

        if (!$succeeded) {
            return false;
        }

        $user = $this->guard->user();
        $subject = $user instanceof User ? $user : null;
        $causerId = $subject?->id;

        ($this->recordAuthActivity)(
            event: 'login',
            subject: $subject,
            causerId: $causerId,
            context: ['outcome' => 'success'],
        );

        return true;
    }
}
