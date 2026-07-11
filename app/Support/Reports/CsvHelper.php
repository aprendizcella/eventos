<?php

declare(strict_types=1);

namespace App\Support\Reports;

/**
 * Helper for safe CSV generation.
 */
final class CsvHelper
{
    /**
     * Sanitize a CSV field to prevent formula injection.
     *
     * Spreadsheet formulas starting with =, +, -, @ are prefixed with a tab
     * so they render as plain text instead of executing.
     */
    public static function sanitizeField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $first = $value[0];

        if (in_array($first, ['=', '+', '-', '@'], true)) {
            return "\t".$value;
        }

        return $value;
    }

    /**
     * Sanitize all string fields in a CSV row array.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function sanitizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = self::sanitizeField($value);
            }
        }

        return $row;
    }
}
