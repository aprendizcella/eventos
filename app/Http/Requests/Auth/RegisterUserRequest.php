<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Auth\RegisterUserDto;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): RegisterUserDto
    {
        $data = $this->validated();

        return new RegisterUserDto(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
        );
    }
}
