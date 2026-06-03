<?php

namespace Database\Seeders;

use App\Models\ComplaintMaster;
use App\Models\DiagnosisMaster;
use Illuminate\Database\Seeder;

class ClinicalMasterSeeder extends Seeder
{
    public function run(): void
    {
        $complaints = [
            'Fever',
            'Cough',
            'Headache',
            'Body pain',
            'Chest pain',
            'Abdominal pain',
            'Vomiting',
            'Diarrhea',
            'Shortness of breath',
            'Sore throat',
            'Dizziness',
            'Weakness',
        ];

        foreach ($complaints as $name) {
            ComplaintMaster::firstOrCreate(
                ['complaint_name' => $name],
                ['complaint_name' => $name]
            );
        }

        $diagnoses = [
            'Upper respiratory tract infection',
            'Gastroenteritis',
            'Hypertension',
            'Diabetes mellitus',
            'Migraine',
            'Viral fever',
            'Allergic rhinitis',
            'Acute bronchitis',
            'Gastritis',
            'Anemia',
        ];

        foreach ($diagnoses as $name) {
            DiagnosisMaster::firstOrCreate(
                ['diagnosis_name' => $name],
                ['diagnosis_name' => $name]
            );
        }
    }
}
