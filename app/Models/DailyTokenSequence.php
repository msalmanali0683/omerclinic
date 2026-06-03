<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyTokenSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_date',
        'last_token_number',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'token_date' => 'date',
        'last_token_number' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
