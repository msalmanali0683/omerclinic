<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class MedicineTypes
{
    public const ALLOWED = [
        'Tab.',
        'Cap.',
        'Syp.',
        'Mix.',
        'Inj.',
    ];

    /** @return list<string> */
    public static function allowed(): array
    {
        return self::ALLOWED;
    }

    public static function validationRule(): \Illuminate\Validation\Rules\In
    {
        return Rule::in(self::ALLOWED);
    }

    public static function validateNewMasterRow(?string $medicineId, ?string $mdcnName, ?string $mdcnType): ?string
    {
        if ($medicineId !== null && $medicineId !== '') {
            return null;
        }

        if (trim((string) $mdcnName) === '') {
            return null;
        }

        $type = trim((string) $mdcnType);

        if ($type === '' || ! in_array($type, self::ALLOWED, true)) {
            return 'Select a valid medicine type (Tab., Cap., Syp., Mix., or Inj.).';
        }

        return null;
    }

    public static function normalize(?string $type): string
    {
        $value = trim((string) $type);

        if ($value === '') {
            return '';
        }

        if (in_array($value, self::ALLOWED, true)) {
            return $value;
        }

        $key = strtolower(rtrim($value, '.'));

        return match ($key) {
            'tab', 'tablet', 'tablets' => 'Tab.',
            'cap', 'capsule', 'capsules' => 'Cap.',
            'syp', 'syrup', 'syrups' => 'Syp.',
            'inj', 'injection', 'injections' => 'Inj.',
            'mix' => 'Mix.',
            default => 'Mix.',
        };
    }

    public static function isInjectionType(?string $type): bool
    {
        $normalized = self::normalize($type);

        return $normalized === 'Inj.';
    }
}
