<?php

namespace App\Models\Concerns;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPatientIncludingTrashed
{
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }
}
