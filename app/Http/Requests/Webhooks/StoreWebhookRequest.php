<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use App\DataTransferObjects\Webhooks\CreateWebhookDto;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048'],
        ];
    }

    /**
     * @return list<callable(\Illuminate\Contracts\Validation\Validator):void>
     */
    public function after(): array
    {
        return [function (\Illuminate\Contracts\Validation\Validator $validator): void {
            $endpoint = $this->string('endpoint')->toString();
            $parts = parse_url($endpoint);
            $host = is_array($parts) ? ($parts['host'] ?? null) : null;

            if (!is_array($parts)
                || ($parts['scheme'] ?? null) !== 'https'
                || !is_string($host)
                || isset($parts['user'], $parts['pass'])
                || ($parts['port'] ?? 443) !== 443
                || filter_var($host, FILTER_VALIDATE_IP) !== false
                || !preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $host)) {
                $validator->errors()->add('endpoint', 'The endpoint must be an HTTPS public hostname on port 443.');
            }
        }];
    }

    public function toDto(): CreateWebhookDto
    {
        return new CreateWebhookDto(endpoint: $this->string('endpoint')->toString());
    }
}
