<?php

/**
 * Remove injection-type medicines from medicines.json.
 * Usage: php database/seeders/scripts/remove_injection_medicines.php
 */

$path = __DIR__ . '/../data/medicines.json';

$rows = json_decode(file_get_contents($path), true);

if (! is_array($rows)) {
    fwrite(STDERR, "Invalid JSON\n");
    exit(1);
}

function isInjectionType(?string $type): bool
{
    $key = strtolower(rtrim(trim((string) $type), '.'));

    return str_starts_with($key, 'inj') || $key === 'injection' || $key === 'injections';
}

$before = count($rows);
$filtered = array_values(array_filter($rows, fn (array $row) => ! isInjectionType($row['mdcn_type'] ?? '')));
$removed = $before - count($filtered);

file_put_contents(
    $path,
    json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);

echo "Removed {$removed} injection medicines\n";
echo 'Remaining: '.count($filtered)."\n";
