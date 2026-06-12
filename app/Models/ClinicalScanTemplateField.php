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
        'group_label',
        'field_key',
        'field_type',
        'options',
        'default_value',
        'default_values',
        'placeholder',
        'is_required',
        'print_in_box',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'options'         => 'array',
        'default_values'  => 'array',
        'is_required'     => 'boolean',
        'print_in_box'    => 'boolean',
    ];

    /**
     * @return list<string>
     */
    public function resolvedDefaultValues(): array
    {
        if (is_array($this->default_values) && $this->default_values !== []) {
            return array_values(array_filter(
                array_map(static fn ($value) => trim((string) $value), $this->default_values),
                static fn ($value) => $value !== ''
            ));
        }

        if ($this->default_value !== null && trim($this->default_value) !== '') {
            return [trim($this->default_value)];
        }

        return [];
    }

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
