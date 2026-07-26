<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use Closure;

class WebhookDestinationPolicy
{
    /**
     * @param  Closure(string): list<string>|null  $resolver  Optional resolver for deterministic tests.
     */
    public function assertSafe(string $endpoint, ?Closure $resolver = null): void
    {
        $parts = parse_url($endpoint);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;

        if (!is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($host)
            || isset($parts['user'], $parts['pass'])
            || ($parts['port'] ?? 443) !== 443
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || !preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $host)) {
            throw UnsafeWebhookDestinationException::forEndpoint();
        }

        $addresses = $resolver instanceof Closure
            ? $resolver($host)
            : $this->resolve($host);

        if ($addresses === [] || array_any($addresses, fn (string $address): bool => !$this->isPublicIp($address))) {
            throw UnsafeWebhookDestinationException::forEndpoint();
        }
    }

    /** @return list<string> */
    private function resolve(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $record): mixed => $record['ip'] ?? $record['ipv6'] ?? null,
            $records,
        ), is_string(...)));
    }

    private function isPublicIp(string $address): bool
    {
        if (filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false) {
            return false;
        }

        $packed = inet_pton($address);

        if ($packed === false) {
            return false;
        }

        $isPublic = true;

        if (strlen($packed) === 4) {
            $value = sprintf('%u', ip2long($address));

            $isPublic = !($value >= 0 && $value <= 16777215)
                && !($value >= 167772160 && $value <= 184549375)
                && !($value >= 1681915904 && $value <= 1686110207)
                && !($value >= 2130706432 && $value <= 2147483647)
                && !($value >= 2851995648 && $value <= 2852061183)
                && !($value >= 3221225984 && $value <= 3221226239)
                && !($value >= 3323068416 && $value <= 3323199487)
                && !($value >= 3325256704 && $value <= 3325256959)
                && !($value >= 3405803776 && $value <= 3405804031)
                && $value < 3758096384;
        } else {
            $firstByte = ord($packed[0]);
            $isPublic = $firstByte < 224 && !str_starts_with(strtolower($address), '2001:db8:');
        }

        return $isPublic;
    }
}
