export function isEmptyScanFieldValue(value) {
    const fieldValue = formatScanFieldValue(value);

    return fieldValue === '';
}

export function formatScanFieldLabel(value) {
    const groupLabel = String(value?.group_label ?? '').replace(/\s+/g, ' ').trim();
    const fieldLabel = String(value?.field_label ?? '').replace(/\s+/g, ' ').trim();

    if (groupLabel && fieldLabel && fieldLabel.toLowerCase() !== groupLabel.toLowerCase()) {
        return `${groupLabel} (${fieldLabel})`;
    }

    if (groupLabel) {
        return groupLabel;
    }

    return fieldLabel;
}

export function formatScanGroupLabel(group) {
    return String(group?.label ?? group?.group_label ?? '').replace(/\s+/g, ' ').trim();
}

export function formatScanGroupValue(group) {
    const parts = (group?.values ?? [])
        .map((value) => {
            const subLabel = String(value?.field_label ?? '').replace(/\s+/g, ' ').trim();
            const fieldValue = formatScanFieldValue(value);

            if (!fieldValue) {
                return '';
            }

            if (group?.is_multi_value && subLabel && subLabel.toLowerCase() !== formatScanGroupLabel(group).toLowerCase()) {
                return `${subLabel}: ${fieldValue}`;
            }

            return fieldValue;
        })
        .filter(Boolean);

    return parts.join('\n');
}

export function groupScanValuesForPrint(values = []) {
    const sorted = [...values].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    const groups = [];

    for (const value of sorted) {
        if (isEmptyScanFieldValue(value)) {
            continue;
        }

        if (value.group_label) {
            const existing = groups.find((group) => group.group_label === value.group_label);

            if (existing) {
                existing.values.push(value);
                continue;
            }

            groups.push({
                id: `group-${value.group_label}`,
                group_label: value.group_label,
                label: value.group_label,
                is_multi_value: true,
                values: [value],
            });
            continue;
        }

        groups.push({
            id: value.id || value.field_key || value.field_label,
            group_label: null,
            label: value.field_label,
            is_multi_value: false,
            values: [value],
        });
    }

    return groups;
}

export function isLongScanGroupValue(group) {
    const text = formatScanGroupValue(group);

    if (!text) {
        return false;
    }

    if (text.includes('\n')) {
        return true;
    }

    return text.length > 48 || (group?.values?.length ?? 0) > 1;
}

export function formatScanFieldValue(value) {
    const raw = value?.field_value;

    if (raw == null) {
        return '';
    }

    return String(raw)
        .split(/\r?\n/)
        .map((line) => line.replace(/[^\S\r\n]+/g, ' ').trim())
        .join('\n')
        .trim();
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
                ? formatScanFieldValue({ field_value: scan.impression })
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
        const groupedValues = groupScanValuesForPrint(printableValues);
        const normalGroupedValues = groupedValues.filter((group) => !group.values.some(isImpressionField));
        const impressionGroupedValues = groupedValues.filter((group) => group.values.some(isImpressionField));

        return {
            ...scan,
            values: printableValues,
            groupedValues,
            normalGroupedValues,
            impressionGroupedValues,
            ...partitionScanValues(printableValues),
        };
    });
}
