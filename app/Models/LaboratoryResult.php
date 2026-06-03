<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryResult extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id',
        'patient_visit_id',
        'laboratory_test_template_id',
        'test_name',
        'test_code',
        'test_price',
        'lab_operator_id',
        'result_date',
        'result_time',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'result_date' => 'date',
        'test_price'  => 'decimal:2',
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
        return $this->belongsTo(LaboratoryTestTemplate::class, 'laboratory_test_template_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(LaboratoryResultValue::class)->orderBy('sort_order');
    }

    public function labOperator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lab_operator_id');
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
