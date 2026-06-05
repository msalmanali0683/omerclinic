<?php

namespace Database\Seeders;

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

        $this->call(MedicineFromExcelSeeder::class);
    }
}
