<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AttendeeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendBulkMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'product_price_id' => ['nullable', 'integer', 'exists:product_price,product_price_id'],
            'attendee_status' => ['nullable', 'string', Rule::in(array_map(fn ($s) => $s->value, AttendeeStatus::cases()))],
            'check_in_status' => ['nullable', 'string', Rule::in(['checked_in', 'not_checked_in'])],
        ];
    }
}
