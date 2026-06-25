<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\ResetPasswordDto;
use App\Models\User;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;

final readonly class ResetPasswordAction
{
    public function __construct(
        private PasswordBroker $broker,
        private RecordAuthActivityAction $recordAuthActivity,
    ) {}

    public function __invoke(ResetPasswordDto $dto): string
    {
        $resetUser = null;

        $status = $this->broker->reset(
            [
                'email' => $dto->email,
                'token' => $dto->token,
                'password' => $dto->password,
                'password_confirmation' => $dto->password,
            ],
            static function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill(['password' => $password])->save();
                $resetUser = $user;
            },
        );

        if ($status === Password::PASSWORD_RESET && $resetUser instanceof User) {
            ($this->recordAuthActivity)(
                event: 'password-reset-completed',
                subject: $resetUser,
                causerId: $resetUser->id,
                context: ['outcome' => 'reset'],
            );
        }

        return $status;
    }
}
