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

];
