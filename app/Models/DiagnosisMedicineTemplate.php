<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosisMedicineTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'diagnosis_master_id',
        'medicine_id',
        'mdcn_type',
        'mdcn_name',
        'mdcn_size',
        'mdcn_time_id',
        'mdcn_dose_from_meal_id',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(DiagnosisMaster::class, 'diagnosis_master_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function doseTime(): BelongsTo
    {
        return $this->belongsTo(MedicineDoseTime::class, 'mdcn_time_id');
    }

    public function doseFromMeal(): BelongsTo
    {
        return $this->belongsTo(MedicineDoseFromMeal::class, 'mdcn_dose_from_meal_id');
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
