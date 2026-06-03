<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComplaintMaster extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'complaint_name',
        'created_by',
        'updated_by',
    ];

    public function visitComplaints(): HasMany
    {
        return $this->hasMany(PatientVisitComplaint::class, 'complaint_master_id');
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
