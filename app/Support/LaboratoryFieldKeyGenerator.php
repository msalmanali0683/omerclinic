<?php

namespace App\Support;

class LaboratoryFieldKeyGenerator
{
    public static function fromLabel(string $label, ?string $fallback = null): string
    {
        $key = strtolower($label);
        $key = preg_replace('/[^a-z0-9\s_\-]/', '', $key) ?? '';
        $key = preg_replace('/[\s\-]+/', '_', $key) ?? '';
        $key = trim($key, '_');

        if ($key === '') {
            return $fallback ?? 'field';
        }

        return $key;
    }
}
