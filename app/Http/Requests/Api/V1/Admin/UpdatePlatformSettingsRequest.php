<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer'],
            'commission' => ['nullable', 'array'],
            'commission.platform_fee_percentage' => ['nullable', 'integer', 'min:0'],
            'commission.platform_fee_fixed' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
