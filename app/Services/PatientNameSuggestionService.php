<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Collection;

class PatientNameSuggestionService
{
    public function suggest(string $query, string $field = 'patient_name'): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        if (! str_contains($query, ' ')) {
            return $this->wordSuggestions($query, $field);
        }

        return $this->completionSuggestions($query, $field);
    }

    protected function wordSuggestions(string $partial, string $field): array
    {
        if (mb_strlen($partial) < 1) {
            return [];
        }

        $needle = mb_strtolower($partial);
        $words = [];

        foreach ($this->matchingNamesForWordSearch($field, $needle) as $name) {
            $parts = preg_split('/\s+/u', trim($name));
            $firstWord = $parts[0] ?? '';

            if ($firstWord !== '' && str_starts_with(mb_strtolower($firstWord), $needle)) {
                $words[$firstWord] = true;
            }
        }

        return $this->formatWordResults($words);
    }

    protected function completionSuggestions(string $prefix, string $field): array
    {
        $like = mb_strtolower($prefix).'%';
        $values = collect();

        foreach ($this->sourceColumns($field) as $column) {
            $values = $values->merge($this->distinctColumnMatches($column, $like));
        }

        return $values
            ->unique()
            ->sort()
            ->take(10)
            ->map(fn (string $value) => [
                'type'  => 'completion',
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    protected function matchingNamesForWordSearch(string $field, string $needle): Collection
    {
        $values = collect();

        foreach ($this->sourceColumns($field) as $column) {
            $values = $values->merge($this->columnPrefixMatches($column, $needle));
        }

        return $values->unique()->values();
    }

    protected function sourceColumns(string $field): array
    {
        return match ($field) {
            'patient_father_name' => ['patient_father_name', 'patient_name'],
            'patient_address'     => ['patient_address'],
            default               => ['patient_name'],
        };
    }

    protected function columnPrefixMatches(string $column, string $needle): Collection
    {
        return Patient::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->whereRaw('LOWER(TRIM('.$column.')) LIKE ?', [$needle.'%'])
            ->orderBy($column)
            ->limit(100)
            ->pluck($column);
    }

    protected function distinctColumnMatches(string $column, string $like): Collection
    {
        return Patient::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->whereRaw('LOWER(TRIM('.$column.')) LIKE ?', [$like])
            ->distinct()
            ->orderBy($column)
            ->limit(10)
            ->pluck($column);
    }

    protected function formatWordResults(array $words): array
    {
        return collect(array_keys($words))
            ->sort()
            ->take(10)
            ->map(fn (string $word) => [
                'type'  => 'word',
                'value' => $word,
            ])
            ->values()
            ->all();
    }
}
