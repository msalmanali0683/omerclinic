<?php



namespace App\Http\Requests;



use App\Support\ActiveRecordValidation;

use Illuminate\Foundation\Http\FormRequest;



class StoreMedicineDoseFromMealRequest extends FormRequest

{

    public function authorize(): bool

    {

        return $this->user()->can('create', \App\Models\MedicineDoseFromMeal::class);

    }



    public function rules(): array

    {

        return [

            'dose_from_meal' => ['required', 'string', 'max:255', ActiveRecordValidation::unique('medicine_dose_from_meals', 'dose_from_meal')],

        ];

    }

}

