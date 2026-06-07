<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mr_number',
        'user_id',
        'doctor_id',
        'name',
        'email',
        'phone',
        'medical_history',
        'limited_info',
        'patient_name',
        'patient_father_name',
        'patient_gender',
        'patient_age',
        'patient_age_unit',
        'patient_cell',
        'patient_address',
        'patient_cnic',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            if ($patient->patient_name && ! $patient->name) {
                $patient->name = $patient->patient_name;
            } elseif ($patient->name && ! $patient->patient_name) {
                $patient->patient_name = $patient->name;
            }

            if ($patient->patient_cell && ! $patient->phone) {
                $patient->phone = $patient->patient_cell;
            } elseif ($patient->phone && ! $patient->patient_cell) {
                $patient->patient_cell = $patient->phone;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PatientVisit::class);
    }

    public function scopeWithInQueueTodayFlag($query)
    {
        return $query->withExists(['visits as in_queue_today' => function ($visitQuery) {
            $visitQuery->whereDate('visit_date', today())
                ->whereIn('status', PatientVisit::ACTIVE_STATUSES);
        }]);
    }

    public function isInQueueToday(): bool
    {
        if (array_key_exists('in_queue_today', $this->attributes)) {
            return (bool) $this->in_queue_today;
        }

        return $this->visits()
            ->whereDate('visit_date', today())
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->exists();
    }

    public function visitTokens(): HasMany
    {
        return $this->hasMany(PatientVisitToken::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(PatientVisit::class)->latestOfMany();
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(PatientVital::class);
    }

    public function latestVitals(): HasOne
    {
        return $this->hasOne(PatientVital::class)->latestOfMany('recorded_at');
    }

    public function visitComplaints(): HasMany
    {
        return $this->hasMany(PatientVisitComplaint::class);
    }

    public function visitDiagnoses(): HasMany
    {
        return $this->hasMany(PatientVisitDiagnosis::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function clinicalScans(): HasMany
    {
        return $this->hasMany(ClinicalScan::class);
    }

    public function laboratoryResults(): HasMany
    {
        return $this->hasMany(LaboratoryResult::class);
    }

    public function laboratoryBills(): HasMany
    {
        return $this->hasMany(LaboratoryBill::class);
    }

    public function prescriptionMedicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->useLogName('patient');
    }
}
