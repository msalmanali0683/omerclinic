#!/usr/bin/env python3
"""Convert medicine.xlsx -> database/seeders/data/medicines.json (no injections)."""

import json
import sys
from pathlib import Path

try:
    import openpyxl
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'openpyxl', '-q'])
    import openpyxl

ROOT = Path(__file__).resolve().parents[2]
DEFAULT_MEDICINE_XLSX = Path(r'c:\Users\Errors\Documents\medicine.xlsx')
OUT_FILE = ROOT / 'seeders' / 'data' / 'medicines.json'


def is_injection_type(raw_type):
    key = str(raw_type or '').strip().rstrip('.').lower()
    return key.startswith('inj') or key in ('injection', 'injections')


def normalize_type(raw_type):
    if is_injection_type(raw_type):
        return None

    value = str(raw_type or '').strip()
    key = value.rstrip('.').lower()

    if key in ('tab', 'tablet', 'tablets'):
        return 'Tab.'
    if key in ('cap', 'capsule', 'capsules'):
        return 'Cap.'
    if key in ('syp', 'syrup', 'syrups'):
        return 'Syp.'
    if key == 'mix':
        return 'Mix.'

    return 'Mix.'


def normalize_size(gm):
    if gm is None:
        return None
    s = str(gm).strip()
    if not s or s.lower() in ('nill', 'nil', 'null', 'none', '-'):
        return None
    return s


def dedupe_key(row):
    return (
        row['mdcn_type'],
        row['mdcn_name'].strip().lower(),
        row['mdcn_size'] or '',
    )


def parse_medicine_xlsx(path: Path):
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = wb['medicine']
    rows = []

    for i, row in enumerate(ws.iter_rows(values_only=True)):
        if i == 0:
            continue

        _, typ, name, gm, *_rest = row[:7]
        mdcn_type = normalize_type(typ)
        mdcn_name = str(name).strip() if name else ''
        mdcn_size = normalize_size(gm)

        if not mdcn_name or mdcn_type is None:
            continue

        rows.append({
            'mdcn_type': mdcn_type,
            'mdcn_name': mdcn_name,
            'mdcn_size': mdcn_size,
        })

    wb.close()
    return rows


def merge_rows(*sources):
    merged = []
    seen = set()
    skipped = 0

    for source in sources:
        for row in source:
            key = dedupe_key(row)
            if key in seen:
                skipped += 1
                continue
            seen.add(key)
            merged.append(row)

    return merged, skipped


def write_json(rows):
    OUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    with OUT_FILE.open('w', encoding='utf-8') as f:
        json.dump(rows, f, ensure_ascii=False, indent=2)
        f.write('\n')


def main():
    medicine_path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_MEDICINE_XLSX

    if not medicine_path.exists():
        print(f'Medicine file not found: {medicine_path}')
        sys.exit(1)

    medicine_rows = parse_medicine_xlsx(medicine_path)
    merged, skipped = merge_rows(medicine_rows)
    write_json(merged)

    print(f'Wrote {len(merged)} medicines to {OUT_FILE}')
    print(f'  from medicine.xlsx: {len(medicine_rows)}')
    print(f'Skipped duplicate rows: {skipped}')


if __name__ == '__main__':
    main()
