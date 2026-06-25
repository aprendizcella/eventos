<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DataTransferObjects\Auth\RequestPasswordResetDto;
use Illuminate\Foundation\Http\FormRequest;

final class RequestPasswordResetRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function toDto(): RequestPasswordResetDto
    {
        $data = $this->validated();

        return new RequestPasswordResetDto(
            email: (string) $data['email'],
        );
    }
}
