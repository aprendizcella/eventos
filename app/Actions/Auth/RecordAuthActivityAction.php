<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Database\Eloquent\Model;
use Throwable;

use function activity;
use function report;

/**
 * Safe boundary around auth audit logging.
 *
 * Records explicit authentication activities (register, login, logout, password
 * reset request/completion) through Spatie Activitylog while guaranteeing:
 *
 *  - Only an allowlisted set of privacy-safe metadata is ever persisted. Any
 *    credential, token, or unknown payload key is stripped before Activitylog
 *    is touched, so a logging failure can never leak a secret either.
 *  - Logging failures never change the auth response. The Activitylog call is
 *    wrapped in a Throwable catch; a generic sanitized warning is reported
 *    through Laravel's reporting channel and the Action returns normally.
 *
 * No exception message, stack trace, password, reset token, raw payload, or
 * internal logger detail ever reaches session flash data or error responses.
 */
final class RecordAuthActivityAction
{
    /** Log name used for all explicit auth audit records. */
    public const string LOG_NAME = 'auth';

    /**
     * Privacy-safe properties that MAY be persisted. Everything else
     * (passwords, tokens, secrets, arbitrary request input) is dropped.
     *
     * @var list<string>
     */
    public const array ALLOWED_PROPERTIES = ['outcome', 'ip', 'user_agent'];

    /**
     * @param  array<string, mixed>  $context  Raw context (sanitized internally).
     */
    public function __invoke(
        string $event,
        ?Model $subject = null,
        ?int $causerId = null,
        array $context = [],
    ): void {
        $properties = self::sanitize($context);

        try {
            $logger = activity(self::LOG_NAME)
                ->event($event)
                ->withProperties($properties);

            if ($subject instanceof Model) {
                $logger->performedOn($subject);
            }

            if ($causerId !== null) {
                $logger->causedBy($causerId);
            }

            $logger->log($event);
        } catch (Throwable) {
            // Report a generic, sanitized warning only. Never include the
            // original exception message, stack trace, or raw context, which
            // could carry secrets. The auth flow response stays unchanged.
            //
            // json_encode is intentionally called WITHOUT JSON_THROW_ON_ERROR:
            // the sanitized context may still hold an unserializable value
            // (a resource, a circular object) reaching this branch via an
            // allowlisted key. A throwing encode here would escape the catch
            // and break the auth response — exactly what this boundary exists
            // to prevent. A false return is rendered as an empty payload.
            $payload = json_encode($properties);
            $encoded = $payload === false ? '{}' : $payload;
            report("Auth audit logging failed for event [{$event}]. Sanitized context: {$encoded}");
        }
    }

    /**
     * @param  array<string, mixed>  $context  Raw context that may contain sensitive keys.
     * @return array<string, mixed> Sanitized, allowlisted context safe to persist.
     */
    public static function sanitize(array $context): array
    {
        return array_intersect_key(
            $context,
            array_fill_keys(self::ALLOWED_PROPERTIES, true),
        );
    }
}
