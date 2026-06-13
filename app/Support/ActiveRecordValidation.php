<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ActiveRecordValidation
{
    public static function unique(string $table, string $column, mixed $ignore = null): Unique
    {
        $rule = Rule::unique($table, $column)->whereNull('deleted_at');

        if ($ignore !== null) {
            $rule->ignore($ignore);
        }

        return $rule;
    }
}
