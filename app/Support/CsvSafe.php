<?php

namespace App\Support;

/**
 * CSV / formula injection guard (OWASP): spreadsheet apps run a cell that starts with
 * = + - @ (or a tab or carriage return) as a formula. Prefixing ' makes it plain text.
 */
final class CsvSafe
{
    private const TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return ($value !== '' && in_array($value[0], self::TRIGGERS, true)) ? "'".$value : $value;
    }
}