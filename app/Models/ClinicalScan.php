<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalScan extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id',
        'patient_visit_id',
        'clinical_scan_template_id',
        'scan_template_name',
        'scan_name',
        'scan_operator_id',
        'scan_date',
        'scan_time',
        'status',
        'notes',
        'impression',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scan_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatientVisit::class, 'patient_visit_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClinicalScanTemplate::class, 'clinical_scan_template_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ClinicalScanValue::class)->orderBy('sort_order');
    }

    public function scanOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scan_operator_id');
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
