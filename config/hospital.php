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
    | Public lab reports page (QR code + footer link on lab bills)
    |--------------------------------------------------------------------------
    */
    'lab_reports_path' => env('HOSPITAL_LAB_REPORTS_PATH', '/lab-reports'),

    'lab_reports_bill_footer_text' => 'Scan QR code or open the link below to print your laboratory reports online.',

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
        'medicines.*.mdcn_name',
        'medicines.*.mdcn_size',
        'values.*.field_value',
        'fields.*.field_label',
        'fields.*.placeholder',
        'fields.*.reference_range',
        'fields.*.unit',
        'lines.*.mdcn_name',
        'lines.*.mdcn_size',
    ],

];
