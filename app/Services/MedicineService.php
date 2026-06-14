<?php

namespace App\Services;

use App\Models\Medicine;
use App\Support\MedicineTypes;
use App\Models\User;
use InvalidArgumentException;

class MedicineService
{
    public function findOrCreate(array $data, ?User $user = null): Medicine
    {
        $type = trim((string) ($data['mdcn_type'] ?? ''));
        $name = trim((string) ($data['mdcn_name'] ?? ''));
        $size = $this->normalizeSize($data['mdcn_size'] ?? null);

        if ($name === '') {
            throw new InvalidArgumentException('Medicine name is required.');
        }

        if ($type === '') {
            throw new InvalidArgumentException('Medicine type is required.');
        }

        if (! in_array($type, MedicineTypes::allowed(), true)) {
            throw new InvalidArgumentException('Invalid medicine type.');
        }

        $existing = $this->findExistingMedicine($type, $name, $size);

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $updates = [];

            if (array_key_exists('mdcn_time_id', $data)) {
                $updates['mdcn_time_id'] = $data['mdcn_time_id'] ?: null;
            }

            if (array_key_exists('mdcn_dose_from_meal_id', $data)) {
                $updates['mdcn_dose_from_meal_id'] = $data['mdcn_dose_from_meal_id'] ?: null;
            }

            if ($updates !== []) {
                $updates['updated_by'] = $user?->id;
                $existing->update($updates);
            }

            return $existing->fresh(['doseTime', 'doseFromMeal']);
        }

        return Medicine::create([
            'mdcn_type'              => $type,
            'mdcn_name'              => $name,
            'mdcn_size'              => $size,
            'mdcn_time_id'           => $data['mdcn_time_id'] ?? null,
            'mdcn_dose_from_meal_id' => $data['mdcn_dose_from_meal_id'] ?? null,
            'created_by'             => $user?->id,
            'updated_by'             => $user?->id,
        ]);
    }

    protected function normalizeSize(mixed $size): ?string
    {
        $value = trim((string) ($size ?? ''));

        return $value === '' ? null : $value;
    }

    protected function findExistingMedicine(string $type, string $name, ?string $size): ?Medicine
    {
        $query = Medicine::withTrashed()
            ->where('mdcn_type', $type)
            ->whereRaw('LOWER(mdcn_name) = ?', [mb_strtolower($name)]);

        if ($size === null) {
            $query->where(function ($builder) {
                $builder->whereNull('mdcn_size')
                    ->orWhere('mdcn_size', '');
            });
        } else {
            $query->where('mdcn_size', $size);
        }

        return $query->first();
    }
}
