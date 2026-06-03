<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientVital extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'patient_visit_id',
        'blood_pressure',
        'temperature',
        'weight',
        'pulse_rate',
        'respiratory_rate',
        'notes',
        'recorded_by',
        'updated_by',
        'recorded_at',
    ];

    protected $casts = [
        'temperature'       => 'decimal:2',
        'weight'            => 'decimal:2',
        'pulse_rate'        => 'integer',
        'respiratory_rate'  => 'integer',
        'recorded_at'       => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
