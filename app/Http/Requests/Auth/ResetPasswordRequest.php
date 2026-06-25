<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Auth\ResetPasswordDto;
use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): ResetPasswordDto
    {
        $data = $this->validated();

        return new ResetPasswordDto(
            email: (string) $data['email'],
            token: (string) $data['token'],
            password: (string) $data['password'],
        );
    }
}
