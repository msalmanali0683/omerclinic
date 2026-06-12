<?php

namespace App\Models;

use App\Support\MedicineTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mdcn_type',
        'mdcn_name',
        'mdcn_size',
        'mdcn_time_id',
        'mdcn_dose_from_meal_id',
        'created_by',
        'updated_by',
    ];

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

    public function diagnosisTemplates(): HasMany
    {
        return $this->hasMany(DiagnosisMedicineTemplate::class, 'medicine_id');
    }

    public function displayLabel(): string
    {
        $parts = array_filter([
            MedicineTypes::normalize($this->mdcn_type),
            $this->mdcn_name,
            $this->mdcn_size,
        ]);

        return implode(' ', $parts);
    }
}
