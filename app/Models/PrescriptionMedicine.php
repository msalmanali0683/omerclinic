<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrescriptionMedicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'prescription_id',
        'patient_id',
        'patient_visit_id',
        'medicine_id',
        'mdcn_type',
        'mdcn_name',
        'mdcn_size',
        'mdcn_time_id',
        'mdcn_dose_from_meal_id',
        'dose_time_text',
        'dose_from_meal_text',
        'show_in_treatment_given',
        'instructions',
        'created_by',
        'updated_by',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function doseTime(): BelongsTo
    {
        return $this->belongsTo(MedicineDoseTime::class, 'mdcn_time_id');
    }

    public function doseFromMeal(): BelongsTo
    {
        return $this->belongsTo(MedicineDoseFromMeal::class, 'mdcn_dose_from_meal_id');
    }

    protected function casts(): array
    {
        return [
            'show_in_treatment_given' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
