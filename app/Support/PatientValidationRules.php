<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class PatientValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function profile(?int $patientId = null): array
    {
        return [
            'patient_name'        => 'required|string|max:255',
            'patient_father_name' => 'nullable|string|max:255',
            'patient_gender'      => 'required|string|in:male,female,other',
            'patient_age'         => 'required|integer|min:0|max:150',
            'patient_age_unit'    => 'required|string|in:years,months,days',
            'patient_cell'        => 'required|string|max:30',
            'patient_address'     => 'nullable|string|max:1000',
            'patient_cnic'        => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('patients', 'patient_cnic')->ignore($patientId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function nestedProfile(?int $patientId = null, string $prefix = 'patient'): array
    {
        $rules = ['patient' => 'nullable|array'];

        foreach (self::profile($patientId) as $field => $rule) {
            $rules["{$prefix}.{$field}"] = self::prefixRule($rule, $prefix);
        }

        return $rules;
    }

    /**
     * @param  string|array<int, mixed>  $rule
     * @return string|array<int, mixed>
     */
    protected static function prefixRule(string|array $rule, string $prefix): string|array
    {
        if (is_array($rule)) {
            $copy = $rule;
            $first = $copy[0] ?? null;

            if (is_string($first) && $first === 'required') {
                $copy[0] = 'required_with:'.$prefix;
            }

            return $copy;
        }

        if (str_starts_with($rule, 'required|')) {
            return 'required_with:'.$prefix.'|'.substr($rule, 9);
        }

        if ($rule === 'required') {
            return 'required_with:'.$prefix;
        }

        return $rule;
    }
}
