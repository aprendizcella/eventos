<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\RequestPasswordResetDto;
use App\Models\User;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;

final readonly class RequestPasswordResetAction
{
    public function __construct(
        private PasswordBroker $broker,
        private RecordAuthActivityAction $recordAuthActivity,
    ) {}

    public function __invoke(RequestPasswordResetDto $dto): string
    {
        $status = $this->broker->sendResetLink(['email' => $dto->email]);

        $subject = User::query()->where('email', $dto->email)->first();

        ($this->recordAuthActivity)(
            event: 'password-reset-request',
            subject: $subject,
            context: ['outcome' => $status === Password::RESET_LINK_SENT ? 'sent' : 'not-found'],
        );

        return $status;
    }
}
