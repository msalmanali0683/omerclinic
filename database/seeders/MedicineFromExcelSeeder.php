<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Support\MedicineTypes;
use App\Support\TextCase;
use Illuminate\Database\Seeder;
use RuntimeException;

class MedicineFromExcelSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/medicines.json');

        if (! is_file($path)) {
            $this->command?->warn('Medicines JSON file not found: '.$path);

            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read medicines JSON file.');
        }

        $rows = json_decode($contents, true);

        if (! is_array($rows)) {
            throw new RuntimeException('Invalid medicines JSON file.');
        }

        foreach ($rows as $row) {
            $mdcnName = TextCase::capitalizeWords(mb_strtolower(trim($row['mdcn_name'] ?? '')));

            if ($mdcnName === null || $mdcnName === '') {
                continue;
            }

            $mdcnType = MedicineTypes::normalize($row['mdcn_type'] ?? '');

            Medicine::updateOrCreate(
                [
                    'mdcn_type' => $mdcnType,
                    'mdcn_name' => $mdcnName,
                    'mdcn_size' => $row['mdcn_size'] ?? null,
                ],
                [
                    'mdcn_time_id'           => null,
                    'mdcn_dose_from_meal_id' => null,
                ]
            );
        }

        $this->command?->info('Imported '.count($rows).' medicines from medicines.json.');
    }
}
