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

    public function duplicateGroups(): array
    {
        $groups = [];

        foreach (Medicine::query()->orderBy('id')->get() as $medicine) {
            $key = $this->identityKey(
                $medicine->mdcn_type,
                $medicine->mdcn_name,
                $medicine->mdcn_size,
            );

            $groups[$key] ??= [
                'identity_key' => $key,
                'mdcn_type'      => MedicineTypes::normalize($medicine->mdcn_type),
                'mdcn_name'      => $medicine->mdcn_name,
                'mdcn_size'      => $this->normalizeSize($medicine->mdcn_size),
                'medicines'      => [],
            ];

            $groups[$key]['medicines'][] = [
                'id'         => $medicine->id,
                'mdcn_type'  => $medicine->mdcn_type,
                'mdcn_name'  => $medicine->mdcn_name,
                'mdcn_size'  => $medicine->mdcn_size,
                'created_at' => $medicine->created_at?->toIso8601String(),
            ];
        }

        return array_values(array_filter(
            $groups,
            fn (array $group) => count($group['medicines']) > 1,
        ));
    }

    public function deleteDuplicateMedicines(?User $user = null): array
    {
        $deleted = 0;
        $groupsCleaned = 0;
        $keptIds = [];

        foreach ($this->duplicateGroups() as $group) {
            $medicines = collect($group['medicines'])->sortBy('id')->values();
            $keeper = $medicines->first();
            $keeperModel = Medicine::query()->findOrFail($keeper['id']);
            $keptIds[] = $keeperModel->id;

            foreach ($medicines->slice(1) as $duplicate) {
                $duplicateModel = Medicine::query()->find($duplicate['id']);

                if (! $duplicateModel) {
                    continue;
                }

                $this->reassignMedicineReferences($keeperModel->id, $duplicateModel->id);
                $duplicateModel->delete();
                $deleted++;
            }

            $groupsCleaned++;
        }

        return [
            'groups_cleaned'   => $groupsCleaned,
            'deleted_count'    => $deleted,
            'kept_medicine_ids' => $keptIds,
        ];
    }

    protected function reassignMedicineReferences(int $keeperId, int $duplicateId): void
    {
        \App\Models\PrescriptionMedicine::query()
            ->where('medicine_id', $duplicateId)
            ->update(['medicine_id' => $keeperId]);

        \App\Models\DiagnosisMedicineTemplate::query()
            ->where('medicine_id', $duplicateId)
            ->update(['medicine_id' => $keeperId]);
    }

    public function identityKey(string $type, string $name, mixed $size): string
    {
        $normalizedType = MedicineTypes::normalize(trim($type));
        $normalizedName = mb_strtolower(trim($name));
        $normalizedSize = $this->normalizeSize($size) ?? '';

        return implode('|', [$normalizedType, $normalizedName, $normalizedSize]);
    }
}
