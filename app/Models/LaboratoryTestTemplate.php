<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaboratoryTestTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_STANDARD = 'standard';

    public const TYPE_IMAGING = 'imaging';

    public const TYPES = [self::TYPE_STANDARD, self::TYPE_IMAGING];

    protected $fillable = [
        'test_name',
        'test_code',
        'test_type',
        'test_price',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'test_price' => 'decimal:2',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(LaboratoryTestTemplateField::class)->orderBy('sort_order');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LaboratoryResult::class);
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
