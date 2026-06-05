export function isEmptyScanFieldValue(value) {
    const fieldValue = value?.field_value;

    if (fieldValue == null) {
        return true;
    }

    return String(fieldValue).trim() === '';
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
                ? scan.impression
                : null,
            values: (scan.values ?? []).filter((value) => !isEmptyScanFieldValue(value)),
        }));
}

export function isImpressionField(value) {
    return String(value?.field_key || '').toLowerCase() === 'impression'
        || String(value?.field_label || '').toLowerCase() === 'impression';
}

export function isLongScanValue(value) {
    const label = value?.field_label || '';
    const fieldValue = value?.field_value ?? '';

    if (String(fieldValue).includes('\n')) {
        return true;
    }

    return `${label}: ${fieldValue}`.length > 28;
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
