<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Auth\LoginUserDto;
use Illuminate\Foundation\Http\FormRequest;

final class LoginUserRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): LoginUserDto
    {
        $data = $this->validated();

        return new LoginUserDto(
            email: (string) $data['email'],
            password: (string) $data['password'],
            remember: (bool) ($data['remember'] ?? false),
        );
    }
}
