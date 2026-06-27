<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', function (string $_, mixed $value, Closure $fail): void {
                /** @var \App\Models\User|null $user */
                $user = $this->user();

                if ($user === null || !Hash::check((string) $value, $user->password)) {
                    $fail(__('The current password is incorrect.'));
                }
            }],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function currentPassword(): string
    {
        return (string) $this->validated('current_password');
    }

    public function newPassword(): string
    {
        return (string) $this->validated('password');
    }
}
