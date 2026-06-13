<?php

namespace App\Services;

use App\Models\DiagnosisMaster;
use App\Models\DiagnosisMedicineTemplate;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DiagnosisMedicineTemplateService
{
    public function __construct(protected MedicineService $medicineService) {}

    public function prepareAttributes(array $data): array
    {
        if (! empty($data['medicine_id'])) {
            $medicine = Medicine::query()->find($data['medicine_id']);

            if ($medicine) {
                $data['mdcn_type'] = $data['mdcn_type'] ?? $medicine->mdcn_type;
                $data['mdcn_name'] = $data['mdcn_name'] ?? $medicine->mdcn_name;
                $data['mdcn_size'] = $data['mdcn_size'] ?? $medicine->mdcn_size;
                $data['mdcn_time_id'] = $data['mdcn_time_id'] ?? $medicine->mdcn_time_id;
                $data['mdcn_dose_from_meal_id'] = $data['mdcn_dose_from_meal_id'] ?? $medicine->mdcn_dose_from_meal_id;
            }
        }

        return $data;
    }

    public function syncMedicineMaster(array $data, ?User $user = null): array
    {
        $type = trim((string) ($data['mdcn_type'] ?? ''));
        $name = trim((string) ($data['mdcn_name'] ?? ''));

        if ($type === '' || $name === '') {
            return $data;
        }

        $medicine = $this->medicineService->findOrCreate([
            'mdcn_type'              => $type,
            'mdcn_name'              => $name,
            'mdcn_size'              => $data['mdcn_size'] ?? null,
            'mdcn_time_id'           => $data['mdcn_time_id'] ?? null,
            'mdcn_dose_from_meal_id' => $data['mdcn_dose_from_meal_id'] ?? null,
        ], $user);

        $data['medicine_id'] = $medicine->id;
        $data['mdcn_type'] = $medicine->mdcn_type;
        $data['mdcn_name'] = $medicine->mdcn_name;
        $data['mdcn_size'] = $medicine->mdcn_size;
        $data['mdcn_time_id'] = $data['mdcn_time_id'] ?? $medicine->mdcn_time_id;
        $data['mdcn_dose_from_meal_id'] = $data['mdcn_dose_from_meal_id'] ?? $medicine->mdcn_dose_from_meal_id;

        return $data;
    }

    protected function nextSortOrder(int $diagnosisMasterId): int
    {
        $max = DiagnosisMedicineTemplate::withTrashed()
            ->where('diagnosis_master_id', $diagnosisMasterId)
            ->max('sort_order');

        return ((int) $max) + 1;
    }

    public function assertDiagnosisExists(int $diagnosisMasterId): DiagnosisMaster
    {
        $diagnosis = DiagnosisMaster::query()->find($diagnosisMasterId);

        if (! $diagnosis) {
            throw ValidationException::withMessages([
                'diagnosis_master_id' => ['The selected diagnosis does not exist.'],
            ]);
        }

        return $diagnosis;
    }

    public function assertNotDuplicate(int $diagnosisMasterId, array $data, ?int $exceptId = null): void
    {
        $query = DiagnosisMedicineTemplate::query()
            ->where('diagnosis_master_id', $diagnosisMasterId);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if (! empty($data['medicine_id'])) {
            $exists = (clone $query)->where('medicine_id', $data['medicine_id'])->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'medicine_id' => ['This medicine is already mapped to the selected diagnosis.'],
                ]);
            }

            return;
        }

        $exists = (clone $query)
            ->whereNull('medicine_id')
            ->where('mdcn_type', $data['mdcn_type'] ?? null)
            ->where('mdcn_name', $data['mdcn_name'])
            ->where('mdcn_size', $data['mdcn_size'] ?? null)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'mdcn_name' => ['This medicine mapping already exists for the selected diagnosis.'],
            ]);
        }
    }

    public function applyIndexFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['diagnosis_master_id'])) {
            $query->where('diagnosis_master_id', $filters['diagnosis_master_id']);
        }

        if (! empty($filters['medicine_id'])) {
            $query->where('medicine_id', $filters['medicine_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('mdcn_name', 'like', "%{$term}%")
                    ->orWhere('mdcn_type', 'like', "%{$term}%")
                    ->orWhere('mdcn_size', 'like', "%{$term}%")
                    ->orWhereHas('diagnosis', fn (Builder $q) => $q->where('diagnosis_name', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    public function activeTemplatesForDiagnosis(DiagnosisMaster $diagnosisMaster)
    {
        return DiagnosisMedicineTemplate::query()
            ->with(['doseTime', 'doseFromMeal', 'medicine'])
            ->where('diagnosis_master_id', $diagnosisMaster->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data, User $user): DiagnosisMedicineTemplate
    {
        $data = $this->prepareAttributes($data);
        $data = $this->syncMedicineMaster($data, $user);
        $this->assertDiagnosisExists((int) $data['diagnosis_master_id']);
        $this->assertNotDuplicate((int) $data['diagnosis_master_id'], $data);

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null || $data['sort_order'] === '') {
            $data['sort_order'] = $this->nextSortOrder((int) $data['diagnosis_master_id']);
        }

        return DiagnosisMedicineTemplate::create([
            ...$data,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function update(DiagnosisMedicineTemplate $template, array $data, User $user): DiagnosisMedicineTemplate
    {
        $data = $this->prepareAttributes($data);
        $data = $this->syncMedicineMaster($data, $user);
        $this->assertDiagnosisExists((int) $data['diagnosis_master_id']);
        $this->assertNotDuplicate((int) $data['diagnosis_master_id'], $data, $template->id);

        unset($data['sort_order']);

        $template->update([
            ...$data,
            'updated_by' => $user->id,
        ]);

        return $template->fresh(['doseTime', 'doseFromMeal', 'medicine', 'diagnosis']);
    }
}
