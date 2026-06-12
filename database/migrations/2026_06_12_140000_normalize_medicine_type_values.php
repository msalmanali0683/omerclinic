<?php

use App\Support\MedicineTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeTable('medicines', ['mdcn_type', 'mdcn_name', 'mdcn_size']);
        $this->normalizeTable('prescription_medicines', ['mdcn_type', 'mdcn_name', 'mdcn_size']);
        $this->normalizeTable('diagnosis_medicine_templates', ['mdcn_type', 'mdcn_name', 'mdcn_size']);
    }

    public function down(): void
    {
        // Legacy values cannot be restored.
    }

    private function normalizeTable(string $table, array $identityColumns): void
    {
        if (! $this->tableHasColumn($table, 'mdcn_type')) {
            return;
        }

        DB::table($table)
            ->select(array_merge(['id'], $identityColumns))
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $identityColumns) {
                foreach ($rows as $row) {
                    $currentType = (string) ($row->mdcn_type ?? '');
                    $normalizedType = MedicineTypes::normalize($currentType);

                    if ($normalizedType === '' || $normalizedType === $currentType) {
                        continue;
                    }

                    $duplicateQuery = DB::table($table)
                        ->where('id', '!=', $row->id)
                        ->where('mdcn_type', $normalizedType);

                    foreach ($identityColumns as $column) {
                        if ($column === 'mdcn_type') {
                            continue;
                        }

                        $value = $row->{$column} ?? null;

                        if ($value === null || $value === '') {
                            $duplicateQuery->whereNull($column);
                        } else {
                            $duplicateQuery->where($column, $value);
                        }
                    }

                    if ($duplicateQuery->exists()) {
                        if ($this->tableHasColumn($table, 'deleted_at')) {
                            DB::table($table)->where('id', $row->id)->update(['deleted_at' => now()]);
                        } else {
                            DB::table($table)->where('id', $row->id)->delete();
                        }

                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['mdcn_type' => $normalizedType]);
                }
            });
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
};
