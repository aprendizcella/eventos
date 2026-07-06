<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EventSettingsRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'auto_notify_waitlist' => ['boolean'],
            'auto_reminders' => ['boolean'],
            'sender_email' => ['required', 'email', 'max:255'],
            'sender_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Authorization is enforced by the Volt component via authorize('manageSettings', $event).
     * The request itself only provides static rules, so it stays permissive if injected.
     */
    public function authorize(): bool
    {
        return true;
    }
}
