<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use Illuminate\Database\Seeder;

class MedicineMasterSeeder extends Seeder
{
    public function run(): void
    {
        $doseFromMeals = [
            'کھانے سے پہلے',
            'کھانے کے بعد',
            'دودھ کے ساتھ استعمال کریں',
            'پورے جسم پر مالش کریں',
            'ایک گلاس پانی میں حل کریں',
        ];

        foreach ($doseFromMeals as $value) {
            MedicineDoseFromMeal::updateOrCreate(
                ['dose_from_meal' => $value],
                ['dose_from_meal' => $value]
            );
        }

        $doseTimes = [
            '1+0+0 — صبح',
            '0+0+1 — شام',
            '0+1+0 — دوپہر',
            '1+0+1 — صبح+شام',
            '1+1+1 — صبح+دوپہر+شام',
            '2+0+0 — صبح',
            '0+0+2 — شام',
            '2+0+2 — صبح+شام',
            '2+2+2 — صبح+دوپہر+شام',
            'دو چمچ صبح+دوپہر+شام',
            'دو چمچ صبح + شام',
            'ایک چمچ صبح + دوپہر + شام',
            'ایک چمچ صبح + شام',
            'آدھا 1/2 چمچ صبح +دوپہر+شام',
            'آدھا 1/2 چمچ صبح + شام',
            'آدھا چمچ روزانہ',
            'رات سونے سے پہلے استعمال کریں',
            'ایک روزانہ صبح ناشتہ سے آدھا گھنٹہ پہلے',
            'ایک روزانہ شام کو',
            'ایک روزانہ رات کو سونے سے پہلے',
            'ایک چمچ روزانہ شام کو',
        ];

        foreach ($doseTimes as $value) {
            MedicineDoseTime::updateOrCreate(
                ['dose_time' => $value],
                ['dose_time' => $value]
            );
        }

        $this->seedMedicines();
    }

    protected function seedMedicines(): void
    {
        $doseTimeIds = MedicineDoseTime::pluck('id')->all();
        $doseMealIds = MedicineDoseFromMeal::pluck('id')->all();

        if ($doseTimeIds === [] || $doseMealIds === []) {
            return;
        }

        $types = [
            'Tablet', 'Capsule', 'Syrup', 'Injection', 'Inj',
            'Mix', 'Cream', 'Drops', 'Inhaler', 'Sachet',
        ];

        $baseNames = [
            'Panadol', 'Brufen', 'Augmentin', 'Calpol', 'Zyrtec', 'Flagyl', 'Risek',
            'Nexium', 'Motilium', 'Ventolin', 'Amoxil', 'Ciproflox', 'Azithromycin',
            'Metformin', 'Amlodipine', 'Atorvastatin', 'Losartan', 'Omeprazole',
            'Cetirizine', 'Domperidone', 'ORS', 'Iron Supplement', 'Calcium',
            'Multivitamin', 'Betadine', 'Hydrocort', 'Cough Syrup', 'ORS Plus',
            'Disprin', 'Strepsils', 'Gaviscon', 'Senokot', 'Lactulose', 'Zincovit',
            'Shelcal', 'Neurobion', 'Folic Acid', 'Vitamin D3', 'Ibuprofen',
            'Diclofenac', 'Tramadol', 'Prednisolone', 'Dexamethasone', 'Clarithromycin',
            'Ceftriaxone', 'Insulin Regular', 'Glimepiride', 'Atenolol', 'Furosemide',
        ];

        $sizesByType = [
            'Tablet'    => ['250mg', '500mg', '650mg', '5mg', '10mg', '20mg', '40mg', '80mg'],
            'Capsule'   => ['250mg', '500mg', '20mg', '40mg', '75mg', '150mg'],
            'Syrup'     => ['60ml', '100ml', '120ml', '200ml', '5ml/5ml'],
            'Injection' => ['1ml', '2ml', '5ml', '500mg/vial', '1g/vial'],
            'Inj'       => ['1ml', '2ml', '5ml', '500mg', '1g'],
            'Mix'       => ['100ml', '200ml', '50ml', 'sachet mix'],
            'Cream'     => ['15g', '20g', '30g', '50g'],
            'Drops'     => ['10ml', '15ml', '20ml', '5ml'],
            'Inhaler'   => ['100mcg', '200mcg', '90mcg/dose'],
            'Sachet'    => ['1sachet', '3g', '5g', '10g'],
        ];

        $target = 100;
        $perType = (int) ceil($target / count($types));
        $created = 0;
        $nameIndex = 0;

        foreach ($types as $type) {
            $sizes = $sizesByType[$type] ?? ['Standard'];
            $typeCount = 0;

            while ($typeCount < $perType && $created < $target) {
                $baseName = $baseNames[$nameIndex % count($baseNames)];
                $nameIndex++;
                $size = $sizes[$typeCount % count($sizes)];
                $suffix = $typeCount > 0 ? ' '.$typeCount : '';
                $mdcnName = trim($baseName.$suffix);

                Medicine::updateOrCreate(
                    [
                        'mdcn_type' => $type,
                        'mdcn_name' => $mdcnName,
                        'mdcn_size' => $size,
                    ],
                    [
                        'mdcn_time_id'           => $doseTimeIds[($created) % count($doseTimeIds)],
                        'mdcn_dose_from_meal_id' => $doseMealIds[($created) % count($doseMealIds)],
                    ]
                );

                $typeCount++;
                $created++;
            }
        }
    }
}
