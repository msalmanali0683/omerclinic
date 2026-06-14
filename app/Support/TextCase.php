<?php

namespace App\Support;

class TextCase
{
    /**
     * Uppercase the first letter of each word (after whitespace or line breaks).
     */
    public static function capitalizeWords(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return $value;
        }

        if (str_contains($value, '**')) {
            return self::capitalizeWordsPreservingBoldSegments($value);
        }

        return preg_replace_callback(
            '/(?:^|[\s\r\n]+)\K\p{L}/u',
            static fn (array $matches): string => mb_strtoupper($matches[0]),
            $value
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $patterns  Dot paths; use * for one segment (e.g. medicines.*.mdcn_name)
     * @return array<string, mixed>
     */
    public static function capitalizeInputArray(array $data, array $patterns, string $prefix = ''): array
    {
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $data[$key] = self::capitalizeInputArray($value, $patterns, $path);

                continue;
            }

            if (is_string($value) && self::pathMatches($path, $patterns)) {
                $data[$key] = self::capitalizeWords($value);
            }
        }

        return $data;
    }

    public static function pathMatches(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $regex = '/^'.str_replace('\*', '[^.]+', preg_quote($pattern, '/')).'$/';

            if (preg_match($regex, $path)) {
                return true;
            }
        }

        return false;
    }

    protected static function capitalizeWordsPreservingBoldSegments(string $value): string
    {
        $parts = preg_split('/(\*\*.+?\*\*)/s', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match('/^\*\*(.+)\*\*$/s', $part, $matches)) {
                $result .= '**'.$matches[1].'**';

                continue;
            }

            $result .= self::capitalizeWords($part) ?? $part;
        }

        return $result;
    }
}
