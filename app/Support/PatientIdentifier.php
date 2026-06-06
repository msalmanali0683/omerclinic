<?php

namespace App\Support;

class PatientIdentifier
{
    public static function normalizeDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function normalizeMrNumber(?string $value): string
    {
        return trim((string) $value);
    }
}
