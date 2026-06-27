<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }
}
