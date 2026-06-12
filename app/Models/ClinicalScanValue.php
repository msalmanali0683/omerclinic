<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalScanValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'clinical_scan_id',
        'clinical_scan_template_field_id',
        'field_label',
        'group_label',
        'field_key',
        'field_type',
        'field_value',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(ClinicalScan::class, 'clinical_scan_id');
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(ClinicalScanTemplateField::class, 'clinical_scan_template_field_id');
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
