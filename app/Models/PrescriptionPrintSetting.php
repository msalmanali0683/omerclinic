<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionPrintSetting extends Model
{
    protected $fillable = [
        'active_paper_key',
        'paper_presets',
        'letterhead_height',
        'font_size_base',
        'font_size_vitals',
        'font_size_clinical_scans',
        'font_size_medicines',
        'font_size_medicine_dose',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'paper_presets' => 'array',
            'font_size_base' => 'integer',
            'font_size_vitals' => 'integer',
            'font_size_clinical_scans' => 'integer',
            'font_size_medicines' => 'integer',
            'font_size_medicine_dose' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
