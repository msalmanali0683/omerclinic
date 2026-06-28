<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPatientIncludingTrashed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientVisit extends Model
{
    use BelongsToPatientIncludingTrashed, HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending_prescription';

    public const STATUS_IN_CONSULTATION = 'in_consultation';

    public const STATUS_PRESCRIBED = 'prescribed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_CONSULTATION,
    ];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'queued_by',
        'visit_date',
        'visit_time',
        'status',
        'reason_for_visit',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function queuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'queued_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(PatientVital::class, 'patient_visit_id');
    }

    public function latestVitals(): HasOne
    {
        return $this->hasOne(PatientVital::class, 'patient_visit_id')->latestOfMany('recorded_at');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(PatientVisitComplaint::class, 'patient_visit_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientVisitDiagnosis::class, 'patient_visit_id');
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'patient_visit_id');
    }

    public function token(): HasOne
    {
        return $this->hasOne(PatientVisitToken::class, 'patient_visit_id');
    }

    public function clinicalScans(): HasMany
    {
        return $this->hasMany(ClinicalScan::class, 'patient_visit_id');
    }

    public function laboratoryResults(): HasMany
    {
        return $this->hasMany(LaboratoryResult::class, 'patient_visit_id');
    }

    public function laboratoryBills(): HasMany
    {
        return $this->hasMany(LaboratoryBill::class, 'patient_visit_id');
    }

    public function prescriptionMedicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class, 'patient_visit_id');
    }
}
