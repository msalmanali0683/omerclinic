<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalScanTemplateField extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['text', 'textarea', 'number', 'select', 'checkbox', 'date'];

    protected $fillable = [
        'clinical_scan_template_id',
        'field_label',
        'field_key',
        'field_type',
        'options',
        'default_value',
        'placeholder',
        'is_required',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'options'     => 'array',
        'is_required' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClinicalScanTemplate::class, 'clinical_scan_template_id');
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
