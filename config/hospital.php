<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hospital / clinic display name (prints, reports, headers)
    |--------------------------------------------------------------------------
    */

    'name' => env('HOSPITAL_NAME', env('APP_NAME', 'The Omer Clinic')),

    'lab_report_footer' => [
        'Not valid for court.',
        'This is a computer-generated laboratory report.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Text fields normalized on save (first letter of each word uppercase)
    |--------------------------------------------------------------------------
    */
    'capitalize_field_patterns' => [
        'name',
        'patient_name',
        'patient_father_name',
        'patient_address',
        'reason_for_visit',
        'notes',
        'impression',
        'mdcn_type',
        'mdcn_name',
        'mdcn_size',
        'dose_time',
        'dose_from_meal',
        'complaint_name',
        'complaint_text',
        'diagnosis_name',
        'diagnosis_text',
        'template_name',
        'test_name',
        'description',
        'field_label',
        'placeholder',
        'reference_range',
        'unit',
        'bill_notes',
        'medicines.*.mdcn_type',
        'medicines.*.mdcn_name',
        'medicines.*.mdcn_size',
        'values.*.field_value',
        'fields.*.field_label',
        'fields.*.placeholder',
        'fields.*.reference_range',
        'fields.*.unit',
        'lines.*.mdcn_name',
        'lines.*.mdcn_type',
        'lines.*.mdcn_size',
    ],

];
