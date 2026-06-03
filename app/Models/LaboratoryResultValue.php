<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryResultValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'laboratory_result_id',
        'laboratory_test_template_field_id',
        'field_label',
        'field_key',
        'field_type',
        'field_value',
        'unit',
        'reference_range',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(LaboratoryResult::class, 'laboratory_result_id');
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(LaboratoryTestTemplateField::class, 'laboratory_test_template_field_id');
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
