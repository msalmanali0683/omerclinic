/**
 * Uppercase the first letter of each word (matches server-side TextCase).
 */
export function capitalizeWords(value) {
    if (value == null || typeof value !== 'string') {
        return value;
    }

    const trimmed = value.trim();

    if (!trimmed) {
        return value;
    }

    return value.replace(/(^|[\s\r\n]+)(\p{L})/gu, (match, prefix, letter) => `${prefix}${letter.toUpperCase()}`);
}

const DEFAULT_FIELD_PATTERNS = [
    'name',
    'patient_name',
    'patient_father_name',
    'patient_address',
    'reason_for_visit',
    'notes',
    'impression',
    'mdcn_name',
    'mdcn_size',
    'dose_time',
    'dose_from_meal',
    'complaint_name',
    'complaint_text',
    'diagnosis_name',
    'diagnosis_text',
    'template_name',
    'test_name',
    'description',
    'field_label',
    'placeholder',
    'reference_range',
    'unit',
    'bill_notes',
];

function pathMatches(path, patterns) {
    return patterns.some((pattern) => {
        const regex = new RegExp(`^${pattern.replace(/\./g, '\\.').replace(/\*/g, '[^.]+')}$`);

        return regex.test(path);
    });
}

/**
 * @param {Record<string, unknown>|unknown[]} data
 * @param {string[]} [patterns]
 * @param {string} [prefix]
 */
export function capitalizePayload(data, patterns = DEFAULT_FIELD_PATTERNS, prefix = '') {
    if (Array.isArray(data)) {
        return data.map((item, index) => capitalizePayload(item, patterns, prefix ? `${prefix}.${index}` : String(index)));
    }

    if (data == null || typeof data !== 'object') {
        return data;
    }

    const result = { ...data };

    Object.entries(result).forEach(([key, value]) => {
        const path = prefix ? `${prefix}.${key}` : key;

        if (Array.isArray(value) || (value && typeof value === 'object')) {
            result[key] = capitalizePayload(value, patterns, path);
        } else if (typeof value === 'string' && pathMatches(path, patterns)) {
            result[key] = capitalizeWords(value);
        }
    });

    return result;
}
