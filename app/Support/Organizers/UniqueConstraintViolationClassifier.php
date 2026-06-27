<?php

declare(strict_types=1);

namespace App\Support\Organizers;

use Illuminate\Database\QueryException;

/**
 * Classifies QueryException instances to determine whether they represent
 * unique-constraint violations on specific columns, across SQLite, MySQL and PostgreSQL.
 */
final class UniqueConstraintViolationClassifier
{
    /**
     * Determine whether a QueryException is a unique-constraint violation
     * on the given columns, across SQLite, MySQL and PostgreSQL.
     *
     * @param  list<string>  $columns
     */
    public static function isUniqueViolation(QueryException $e, array $columns = []): bool
    {
        $sqlstate = (string) $e->getCode();
        $message = $e->getMessage();

        // SQLSTATE 23000 = integrity constraint violation (broad).
        // SQLSTATE 23505 = unique violation (PostgreSQL).
        // MySQL uses 23000 with error code 1062 and "Duplicate entry" in the message.
        // SQLite uses 23000 with "UNIQUE constraint failed" in the message.
        $isUniqueSqlstate = $sqlstate === '23505'
            || ($sqlstate === '23000' && (
                str_contains($message, 'UNIQUE constraint failed')
                || str_contains($message, 'Duplicate entry')
                || str_contains($message, 'duplicate key value violates unique constraint')
            ));

        if (!$isUniqueSqlstate) {
            return false;
        }

        if ($columns === []) {
            return true;
        }

        return array_all($columns, fn ($column) => str_contains($message, (string) $column));
    }
}
