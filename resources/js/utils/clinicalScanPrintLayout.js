export function isEmptyScanFieldValue(value) {
    const fieldValue = formatScanFieldValue(value);

    return fieldValue === '';
}

export function formatScanFieldLabel(value) {
    return String(value?.field_label ?? '').replace(/\s+/g, ' ').trim();
}

export function formatScanFieldValue(value) {
    const raw = value?.field_value;

    if (raw == null) {
        return '';
    }

    return String(raw).replace(/\s+/g, ' ').trim();
}

export function scanHasPrintableContent(scan) {
    if (scan?.impression && String(scan.impression).trim() !== '') {
        return true;
    }

    return (scan?.values ?? []).some((value) => !isEmptyScanFieldValue(value));
}

export function filterPrintableClinicalScans(scans = []) {
    return (Array.isArray(scans) ? scans : [])
        .filter(scanHasPrintableContent)
        .map((scan) => ({
            ...scan,
            impression: scan.impression && String(scan.impression).trim() !== ''
                ? String(scan.impression).replace(/\s+/g, ' ').trim()
                : null,
            values: (scan.values ?? []).filter((value) => !isEmptyScanFieldValue(value)),
        }));
}

export function isImpressionField(value) {
    return String(value?.field_key || '').toLowerCase() === 'impression'
        || String(value?.field_label || '').toLowerCase() === 'impression';
}

export function isLongScanValue(value) {
    const fieldValue = formatScanFieldValue(value);

    if (!fieldValue) {
        return false;
    }

    if (fieldValue.includes('\n')) {
        return true;
    }

    return fieldValue.length > 48;
}

export function partitionScanValues(values = []) {
    const normalValues = [];
    const impressionValues = [];

    for (const value of values) {
        if (isImpressionField(value)) {
            impressionValues.push(value);
        } else {
            normalValues.push(value);
        }
    }

    return { normalValues, impressionValues };
}

export function withScanValueLayout(scans = []) {
    return scans.map((scan) => {
        const printableValues = (scan.values ?? []).filter((value) => !isEmptyScanFieldValue(value));

        return {
            ...scan,
            values: printableValues,
            ...partitionScanValues(printableValues),
        };
    });
}
