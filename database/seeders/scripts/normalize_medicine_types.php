<?php

/**
 * Normalize mdcn_type values in medicines.json to standard types.
 * Usage: php database/seeders/scripts/normalize_medicine_types.php
 */

$path = __DIR__ . '/../data/medicines.json';

$rows = json_decode(file_get_contents($path), true);

if (! is_array($rows)) {
    fwrite(STDERR, "Invalid JSON\n");
    exit(1);
}

function normalizeMedicineType(?string $type): string
{
    $value = trim((string) $type);
    $key = strtolower(rtrim($value, '.'));

    return match ($key) {
        'tab', 'tablet', 'tablets' => 'Tab.',
        'cap', 'capsule', 'capsules' => 'Cap.',
        'syp', 'syrup', 'syrups' => 'Syp.',
        'inj', 'injection', 'injections' => 'Inj.',
        'mix' => 'Mix.',
        default => 'Mix.',
    };
}

function isInjectionType(?string $type): bool
{
    $key = strtolower(rtrim(trim((string) $type), '.'));

    return str_starts_with($key, 'inj') || $key === 'injection' || $key === 'injections';
}

$before = count($rows);
$rows = array_values(array_filter($rows, fn (array $row) => ! isInjectionType($row['mdcn_type'] ?? '')));
$removed = $before - count($rows);

foreach ($rows as &$row) {
    $row['mdcn_type'] = normalizeMedicineType($row['mdcn_type'] ?? '');
}
unset($row);

file_put_contents(
    $path,
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);

$afterCounts = [];
foreach ($rows as $row) {
    $afterCounts[$row['mdcn_type']] = ($afterCounts[$row['mdcn_type']] ?? 0) + 1;
}

echo 'Removed '.$removed." injection rows\n";
echo 'Updated '.count($rows)." medicines\n\nAfter normalization:\n";
foreach ($afterCounts as $type => $count) {
    echo "  {$type}: {$count}\n";
}
