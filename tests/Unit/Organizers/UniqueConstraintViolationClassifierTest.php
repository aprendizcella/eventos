<?php

declare(strict_types=1);

use App\Support\Organizers\UniqueConstraintViolationClassifier;
use Illuminate\Database\QueryException;

function makeQueryException(string $sqlstate, string $message, string $sql = 'select 1', array $bindings = []): QueryException
{
    // Exception constructor: message, code, previous
    // QueryException extracts code via $previous->getCode()
    $previous = new Exception($message, (int) ltrim($sqlstate, '0'));

    return new QueryException('test', $sql, $bindings, $previous);
}

it('detects SQLite unique constraint violation with column names', function (): void {
    $e = makeQueryException(
        '23000',
        'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: organizer_user.organizer_id, organizer_user.user_id',
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id', 'user_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e))->toBeTrue();
});

it('detects MySQL unique constraint violation with column names', function (): void {
    $e = makeQueryException(
        '23000',
        "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1-2' for key 'organizer_user_organizer_id_user_id_unique'",
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id', 'user_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e))->toBeTrue();
});

it('detects PostgreSQL unique constraint violation with column names', function (): void {
    $e = makeQueryException(
        '23505',
        'SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "organizer_user_organizer_id_user_id_unique" DETAIL:  Key (organizer_id, user_id)=(1, 2) already exists.',
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id', 'user_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id']))->toBeTrue()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e))->toBeTrue();
});

it('returns false when columns do not match the violation message', function (): void {
    $e = makeQueryException(
        '23000',
        'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: organizer_user.organizer_id, organizer_user.user_id',
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['email']))->toBeFalse()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id', 'email']))->toBeFalse();
});

it('returns false for non-unique QueryException', function (): void {
    $e = makeQueryException(
        '42S02',
        "SQLSTATE[42S02]: Base table or view not found: 1146 Table 'organizer_user' doesn't exist",
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e))->toBeFalse()
        ->and(UniqueConstraintViolationClassifier::isUniqueViolation($e, ['organizer_id']))->toBeFalse();
});

it('returns false for integrity constraint violation that is not unique', function (): void {
    $e = makeQueryException(
        '23000',
        'SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails',
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e))->toBeFalse();
});

it('detects unique violation with empty columns list', function (): void {
    $e = makeQueryException(
        '23000',
        'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: organizer_user.organizer_id, organizer_user.user_id',
    );

    expect(UniqueConstraintViolationClassifier::isUniqueViolation($e, []))->toBeTrue();
});
