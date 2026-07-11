<?php

declare(strict_types=1);

use App\Support\Reports\CsvHelper;
use Tests\TestCase;

uses(TestCase::class);

it('sanitizes fields starting with equals sign', function (): void {
    expect(CsvHelper::sanitizeField('=CMD'))->toBe("\t=CMD");
});

it('sanitizes fields starting with plus sign', function (): void {
    expect(CsvHelper::sanitizeField('+SUM'))->toBe("\t+SUM");
});

it('sanitizes fields starting with minus sign', function (): void {
    expect(CsvHelper::sanitizeField('-1'))->toBe("\t-1");
});

it('sanitizes fields starting with at sign', function (): void {
    expect(CsvHelper::sanitizeField('@DDE'))->toBe("\t@DDE");
});

it('does not sanitize normal text fields', function (): void {
    expect(CsvHelper::sanitizeField('Hello World'))->toBe('Hello World');
});

it('does not sanitize numeric fields', function (): void {
    expect(CsvHelper::sanitizeField('1234'))->toBe('1234');
});

it('handles null values', function (): void {
    expect(CsvHelper::sanitizeField(null))->toBe('');
});

it('handles empty strings', function (): void {
    expect(CsvHelper::sanitizeField(''))->toBe('');
});

it('sanitizes all string fields in a row', function (): void {
    $row = [
        'name' => '=SUM(A1:A10)',
        'amount' => '100',
        'currency' => 'USD',
    ];

    $safe = CsvHelper::sanitizeRow($row);

    expect($safe['name'])->toBe("\t=SUM(A1:A10)")
        ->and($safe['amount'])->toBe('100')
        ->and($safe['currency'])->toBe('USD');
});
