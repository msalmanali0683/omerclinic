<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class MedicineSearch
{
    public static function applyOptionsQuery(Builder $query, ?string $search, ?string $mdcnType): Builder
    {
        if ($mdcnType !== null && trim($mdcnType) !== '') {
            $normalizedType = MedicineTypes::normalize($mdcnType);

            if (in_array($normalizedType, MedicineTypes::allowed(), true)) {
                $query->where('mdcn_type', $normalizedType);
            }
        }

        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $term = mb_strtolower($search);

        return $query->where(function (Builder $builder) use ($term) {
            $builder->whereRaw('LOWER(TRIM(mdcn_name)) LIKE ?', [$term.'%']);

            if (mb_strlen($term) >= 2) {
                $builder->orWhereRaw('LOWER(mdcn_name) LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw('LOWER(COALESCE(mdcn_size, \'\')) LIKE ?', ['%'.$term.'%']);
            }
        });
    }
}
