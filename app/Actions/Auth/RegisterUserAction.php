<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\RegisterUserDto;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;

final readonly class RegisterUserAction
{
    public function __construct(
        private StatefulGuard $guard,
        private RecordAuthActivityAction $recordAuthActivity,
    ) {}

    public function __invoke(RegisterUserDto $dto): User
    {
        $user = User::query()->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        $this->guard->login($user);

        ($this->recordAuthActivity)(
            event: 'register',
            subject: $user,
            causerId: $user->id,
            context: ['outcome' => 'success'],
        );

        return $user;
    }
}
