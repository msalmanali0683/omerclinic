export function isEmptyLabFieldValue(value) {
    const fieldValue = value?.field_value;

    if (fieldValue == null) {
        return true;
    }

    return String(fieldValue).trim() === '';
}

export function labResultHasPrintableContent(result) {
    return (result?.values ?? []).some((value) => !isEmptyLabFieldValue(value));
}

export function filterPrintableLaboratoryResults(results = []) {
    return (Array.isArray(results) ? results : [])
        .filter(labResultHasPrintableContent)
        .map((result) => ({
            ...result,
            values: (result.values ?? []).filter((value) => !isEmptyLabFieldValue(value)),
        }));
}

export function formatLabResultDateTime(result) {
    const parts = [];

    if (result?.result_date) {
        parts.push(result.result_date);
    }

    if (result?.result_time) {
        parts.push(String(result.result_time).slice(0, 5));
    }

    return parts.join(' ');
}

export function isLongLabValue(value) {
    const label = value?.field_label || '';
    const fieldValue = value?.field_value ?? '';
    const unit = value?.unit ? ` ${value.unit}` : '';
    const reference = value?.reference_range ? ` Normal: ${value.reference_range}` : '';

    if (String(fieldValue).includes('\n')) {
        return true;
    }

    return `${label}: ${fieldValue}${unit}${reference}`.length > 42;
}

export function withLabValueLayout(results = []) {
    return results.map((result) => ({
        ...result,
        displayTitle: formatLabResultTitle(result),
    }));
}

export function formatLabResultTitle(result) {
    const base = result?.test_name || 'Laboratory Result';
    const when = formatLabResultDateTime(result);

    return when ? `${base} - ${when}` : base;
}
