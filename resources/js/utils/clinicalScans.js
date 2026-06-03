export const CLINICAL_SCAN_FIELD_TYPES = [
    { value: 'text', label: 'Text' },
    { value: 'textarea', label: 'Textarea' },
    { value: 'number', label: 'Number' },
    { value: 'select', label: 'Select' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'date', label: 'Date' },
];

export const DEFAULT_ABDOMINAL_SCAN_FIELDS = [
    'Liver',
    'Gall Bladder',
    'CBD',
    'Pancreas',
    'Spleen',
    'Right Kidney',
    'Left Kidney',
    'Urinary Bladder',
    'Prostate',
    'Impression',
];

export function createTemplateFieldRow(overrides = {}) {
    return {
        _key: `field-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        id: null,
        field_label: '',
        field_type: 'textarea',
        options: [],
        options_text: '',
        default_value: '',
        placeholder: '',
        is_required: false,
        sort_order: 0,
        ...overrides,
    };
}

export function serializeTemplateFields(fields) {
    return fields
        .filter((field) => field.field_label?.trim())
        .map((field, index) => ({
            id: field.id || undefined,
            field_label: field.field_label.trim(),
            field_type: field.field_type || 'textarea',
            options: field.field_type === 'select' || field.field_type === 'checkbox'
                ? parseOptions(field.options_text)
                : null,
            default_value: field.default_value || null,
            placeholder: field.placeholder || null,
            is_required: !!field.is_required,
            sort_order: field.sort_order || index + 1,
        }));
}

export function mapTemplateFieldToRow(field) {
    return createTemplateFieldRow({
        id: field.id,
        field_label: field.field_label,
        field_type: field.field_type || 'textarea',
        options: field.options || [],
        options_text: Array.isArray(field.options) ? field.options.join('\n') : '',
        default_value: field.default_value || '',
        placeholder: field.placeholder || '',
        is_required: !!field.is_required,
        sort_order: field.sort_order || 0,
    });
}

export function buildDefaultAbdominalFields() {
    return DEFAULT_ABDOMINAL_SCAN_FIELDS.map((label, index) => createTemplateFieldRow({
        field_label: label,
        field_type: label === 'Impression' ? 'textarea' : 'textarea',
        sort_order: index + 1,
    }));
}

export function buildScanValuesFromTemplate(fields, existingValues = []) {
    const existingByFieldId = new Map(
        (existingValues || []).map((value) => [value.clinical_scan_template_field_id, value])
    );

    return (fields || []).map((field) => {
        const existing = existingByFieldId.get(field.id);

        return {
            id: existing?.id || null,
            clinical_scan_template_field_id: field.id,
            field_label: field.field_label,
            field_key: field.field_key,
            field_type: field.field_type,
            field_value: existing?.field_value ?? field.default_value ?? '',
            is_required: !!field.is_required,
            placeholder: field.placeholder || '',
            options: field.options || [],
            sort_order: field.sort_order || 0,
        };
    });
}

export function serializeScanValues(values) {
    return (values || []).map((row) => ({
        id: row.id || undefined,
        clinical_scan_template_field_id: row.clinical_scan_template_field_id,
        field_value: row.field_value ?? null,
    }));
}

function parseOptions(text) {
    if (!text?.trim()) return [];

    return text
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}
