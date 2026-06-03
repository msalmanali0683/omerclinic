<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientMrSequence extends Model
{
    protected $fillable = [
        'year',
        'month',
        'last_sequence',
    ];
}
