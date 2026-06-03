export const LABORATORY_FIELD_TYPES = [
    { value: 'text', label: 'Text' },
    { value: 'textarea', label: 'Textarea' },
    { value: 'number', label: 'Number' },
    { value: 'select', label: 'Select' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'date', label: 'Date' },
];

export function createTemplateFieldRow(overrides = {}) {
    return {
        _key: `field-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        id: null,
        field_label: '',
        field_type: 'text',
        unit: '',
        reference_range: '',
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
            field_type: field.field_type || 'text',
            unit: field.unit?.trim() || null,
            reference_range: field.reference_range?.trim() || null,
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
        field_type: field.field_type || 'text',
        unit: field.unit || '',
        reference_range: field.reference_range || '',
        options: field.options || [],
        options_text: Array.isArray(field.options) ? field.options.join('\n') : '',
        default_value: field.default_value || '',
        placeholder: field.placeholder || '',
        is_required: !!field.is_required,
        sort_order: field.sort_order || 0,
    });
}

export function buildResultValuesFromTemplate(fields, existingValues = []) {
    const existingByFieldId = new Map(
        (existingValues || []).map((value) => [value.laboratory_test_template_field_id, value])
    );

    return (fields || []).map((field) => {
        const existing = existingByFieldId.get(field.id);

        return {
            id: existing?.id || null,
            laboratory_test_template_field_id: field.id,
            field_label: field.field_label,
            field_key: field.field_key,
            field_type: field.field_type,
            unit: field.unit || '',
            reference_range: field.reference_range || '',
            field_value: existing?.field_value ?? field.default_value ?? '',
            is_required: !!field.is_required,
            placeholder: field.placeholder || '',
            options: field.options || [],
            sort_order: field.sort_order || 0,
        };
    });
}

export function serializeResultValues(values) {
    return (values || []).map((row) => ({
        id: row.id || undefined,
        laboratory_test_template_field_id: row.laboratory_test_template_field_id,
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
