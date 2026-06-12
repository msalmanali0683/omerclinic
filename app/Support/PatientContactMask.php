<?php

namespace App\Support;

class PatientContactMask
{
    public static function apply(array $data, ?object $user): array
    {
        if (! $user) {
            return $data;
        }

        if (method_exists($user, 'can') && $user->can('view patients')) {
            return $data;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return $data;
        }

        if (method_exists($user, 'can') && $user->can('view limited patient info')) {
            if (! empty($data['patient_cnic'])) {
                $data['patient_cnic'] = self::maskCnic($data['patient_cnic']);
            }

            if (! empty($data['patient_cell'])) {
                $data['patient_cell'] = self::maskCell($data['patient_cell']);
            }
        }

        return $data;
    }

    public static function maskCnic(?string $cnic): ?string
    {
        if (! $cnic) {
            return $cnic;
        }

        return preg_replace('/^\d{5}-\d{7}-\d$/', '*****-*******-*', $cnic) ?? '*****-*******-*';
    }

    public static function maskCell(?string $cell): ?string
    {
        if (! $cell || strlen($cell) < 4) {
            return $cell;
        }

        return substr($cell, 0, 4).str_repeat('*', max(strlen($cell) - 4, 0));
    }
}
