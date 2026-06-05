<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryResultAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'laboratory_result_id',
        'laboratory_result_value_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'sort_order',
        'created_by',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(LaboratoryResult::class, 'laboratory_result_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(LaboratoryResultValue::class, 'laboratory_result_value_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
