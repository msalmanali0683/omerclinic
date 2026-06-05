#!/usr/bin/env python3
"""Convert medicine.xlsx + inj_list.xlsx -> database/seeders/data/medicines.json"""

import json
import sys
from pathlib import Path

try:
    import openpyxl
except ImportError:
    import subprocess
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'openpyxl', '-q'])
    import openpyxl

TYPE_MAP = {
    'Tab.': 'Tablet',
    'Cap.': 'Capsule',
    'Syp.': 'Syrup',
    'mix': 'Mix',
    'Inj.': 'Inj',
}

ROOT = Path(__file__).resolve().parents[2]
DEFAULT_MEDICINE_XLSX = Path(r'c:\Users\Errors\Documents\medicine.xlsx')
DEFAULT_INJ_XLSX = Path(r'c:\Users\Errors\Documents\inj_list.xlsx')
OUT_FILE = ROOT / 'seeders' / 'data' / 'medicines.json'


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
        raw_type = str(typ).strip() if typ else ''
        mdcn_type = TYPE_MAP.get(raw_type, raw_type or 'Tablet')
        mdcn_name = str(name).strip() if name else ''
        mdcn_size = normalize_size(gm)

        if not mdcn_name:
            continue

        rows.append({
            'mdcn_type': mdcn_type,
            'mdcn_name': mdcn_name,
            'mdcn_size': mdcn_size,
        })

    wb.close()
    return rows


def parse_inj_list_xlsx(path: Path):
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = wb[wb.sheetnames[0]]
    rows = []

    for i, row in enumerate(ws.iter_rows(values_only=True)):
        if i == 0:
            continue

        name = str(row[1]).strip() if len(row) > 1 and row[1] else ''
        if not name:
            continue

        rows.append({
            'mdcn_type': 'Inj',
            'mdcn_name': name,
            'mdcn_size': None,
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
    inj_path = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_INJ_XLSX

    sources = []
    counts = {}

    if medicine_path.exists():
        medicine_rows = parse_medicine_xlsx(medicine_path)
        sources.append(medicine_rows)
        counts['medicine.xlsx'] = len(medicine_rows)
    else:
        print(f'Warning: medicine file not found: {medicine_path}')

    if inj_path.exists():
        inj_rows = parse_inj_list_xlsx(inj_path)
        sources.append(inj_rows)
        counts['inj_list.xlsx'] = len(inj_rows)
    else:
        print(f'Warning: injection file not found: {inj_path}')

    if not sources:
        print('No Excel files found to convert.')
        sys.exit(1)

    merged, skipped = merge_rows(*sources)
    write_json(merged)

    print(f'Wrote {len(merged)} total medicines to {OUT_FILE}')
    for label, count in counts.items():
        print(f'  from {label}: {count}')
    print(f'Skipped duplicate rows: {skipped}')


if __name__ == '__main__':
    main()
