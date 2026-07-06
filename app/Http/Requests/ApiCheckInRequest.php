<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApiCheckInRequest extends FormRequest
{
    /**
     * Authorization is enforced by the controller via Gate::authorize('checkIn', $event)
     * after multi-tenant verification, so the request itself stays permissive.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'unique_code' => ['required', 'string', 'exists:attendee,unique_code'],
            'check_in_list_id' => ['nullable', 'integer', 'exists:check_in_list,check_in_list_id'],
        ];
    }
}
