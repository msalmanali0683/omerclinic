<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosisMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'diagnosis_name',
        'created_by',
        'updated_by',
    ];

    public function visitDiagnoses(): HasMany
    {
        return $this->hasMany(PatientVisitDiagnosis::class, 'diagnosis_master_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function medicineTemplates(): HasMany
    {
        return $this->hasMany(DiagnosisMedicineTemplate::class, 'diagnosis_master_id');
    }
}
