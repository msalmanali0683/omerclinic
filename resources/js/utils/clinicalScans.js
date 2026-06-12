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

export function createTemplateFieldSlot(overrides = {}) {
    return {
        _key: `slot-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        id: null,
        sub_label: '',
        field_type: 'textarea',
        options: [],
        options_text: '',
        default_value: '',
        default_values_text: '',
        placeholder: '',
        is_required: false,
        ...overrides,
    };
}

export function createTemplateFieldGroup(overrides = {}) {
    return {
        _key: `group-${Date.now()}-${Math.random().toString(36).slice(2)}`,
        label: '',
        is_multi_value: false,
        slots: [createTemplateFieldSlot()],
        ...overrides,
    };
}

/** @deprecated use createTemplateFieldGroup */
export function createTemplateFieldRow(overrides = {}) {
    return createTemplateFieldGroup({
        label: overrides.field_label ?? '',
        is_multi_value: !!overrides.group_label,
        slots: [createTemplateFieldSlot({
            id: overrides.id ?? null,
            sub_label: overrides.group_label ? (overrides.field_label ?? '') : '',
            field_type: overrides.field_type ?? 'textarea',
            options: overrides.options ?? [],
            options_text: overrides.options_text ?? '',
            default_value: overrides.default_value ?? '',
            default_values_text: overrides.default_values_text ?? '',
            placeholder: overrides.placeholder ?? '',
            is_required: overrides.is_required ?? false,
        })],
    });
}

export function resolveTemplateDefaultValues(field) {
    if (Array.isArray(field?.default_values) && field.default_values.length) {
        return field.default_values
            .map((value) => String(value).trim())
            .filter(Boolean);
    }

    if (field?.default_value?.trim()) {
        return [field.default_value.trim()];
    }

    return [];
}

function mapFieldToSlot(field) {
    const defaultValues = resolveTemplateDefaultValues(field);

    return createTemplateFieldSlot({
        id: field.id,
        sub_label: field.group_label ? (field.field_label ?? '') : '',
        field_type: field.field_type || 'textarea',
        options: field.options || [],
        options_text: Array.isArray(field.options) ? field.options.join('\n') : '',
        default_value: defaultValues[0] || '',
        default_values_text: defaultValues.join('\n'),
        placeholder: field.placeholder || '',
        is_required: !!field.is_required,
    });
}

export function mapTemplateFieldsToGroups(fields = []) {
    const sorted = [...fields].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    const groups = [];

    for (const field of sorted) {
        if (field.group_label) {
            const existing = groups.find((group) => group.is_multi_value && group.label === field.group_label);

            if (existing) {
                existing.slots.push(mapFieldToSlot(field));
                continue;
            }

            groups.push({
                _key: `group-${field.group_label}-${field.id || groups.length}`,
                label: field.group_label,
                is_multi_value: true,
                slots: [mapFieldToSlot(field)],
            });
            continue;
        }

        groups.push({
            _key: `group-${field.id || field.field_label || groups.length}`,
            label: field.field_label ?? '',
            is_multi_value: false,
            slots: [mapFieldToSlot(field)],
        });
    }

    return groups.length ? groups : [createTemplateFieldGroup()];
}

/** @deprecated use mapTemplateFieldsToGroups */
export function mapTemplateFieldToRow(field) {
    return mapTemplateFieldsToGroups([field])[0];
}

function serializeSlot(fieldLabel, groupLabel, slot, sortOrder) {
    const defaultValues = parseDefaultValues(slot.default_values_text, slot.default_value);

    return {
        id: slot.id || undefined,
        field_label: fieldLabel,
        group_label: groupLabel,
        field_type: slot.field_type || 'textarea',
        options: slot.field_type === 'select' || slot.field_type === 'checkbox'
            ? parseOptions(slot.options_text)
            : null,
        default_value: defaultValues[0] || null,
        default_values: defaultValues.length ? defaultValues : null,
        placeholder: slot.placeholder || null,
        is_required: !!slot.is_required,
        sort_order: sortOrder,
    };
}

export function serializeTemplateGroups(groups = []) {
    const rows = [];
    let sortOrder = 1;

    for (const group of groups) {
        const label = group.label?.trim();
        if (!label) continue;

        const slots = (group.slots ?? []).filter(Boolean);
        if (!slots.length) continue;

        if (!group.is_multi_value || slots.length === 1) {
            rows.push(serializeSlot(label, null, slots[0], sortOrder++));
            continue;
        }

        slots.forEach((slot, index) => {
            const subLabel = slot.sub_label?.trim() || `Value ${index + 1}`;
            rows.push(serializeSlot(subLabel, label, slot, sortOrder++));
        });
    }

    return rows;
}

/** @deprecated use serializeTemplateGroups */
export function serializeTemplateFields(fields) {
    if (Array.isArray(fields) && fields[0]?.slots) {
        return serializeTemplateGroups(fields);
    }

    return serializeTemplateGroups(
        (fields ?? [])
            .filter((field) => field.field_label?.trim())
            .map((field) => createTemplateFieldGroup({
                label: field.group_label || field.field_label,
                is_multi_value: !!field.group_label,
                slots: [createTemplateFieldSlot({
                    id: field.id,
                    sub_label: field.group_label ? field.field_label : '',
                    field_type: field.field_type,
                    options_text: field.options_text,
                    default_values_text: field.default_values_text,
                    placeholder: field.placeholder,
                    is_required: field.is_required,
                })],
            }))
    );
}

export function buildDefaultAbdominalFields() {
    return DEFAULT_ABDOMINAL_SCAN_FIELDS.map((label) => createTemplateFieldGroup({
        label,
        is_multi_value: false,
        slots: [createTemplateFieldSlot({ field_type: 'textarea' })],
    }));
}

export function buildScanValuesFromTemplate(fields, existingValues = []) {
    const existingByFieldId = new Map(
        (existingValues || []).map((value) => [value.clinical_scan_template_field_id, value])
    );

    return (fields || []).map((field) => {
        const existing = existingByFieldId.get(field.id);
        const defaultValues = resolveTemplateDefaultValues(field);

        return {
            id: existing?.id || null,
            clinical_scan_template_field_id: field.id,
            field_label: field.field_label,
            group_label: field.group_label || null,
            field_key: field.field_key,
            field_type: field.field_type,
            field_value: existing?.field_value ?? defaultValues[0] ?? '',
            default_values: defaultValues,
            is_required: !!field.is_required,
            placeholder: field.placeholder || '',
            options: field.options || [],
            sort_order: field.sort_order || 0,
        };
    });
}

export function groupScanFieldsForEntry(values = []) {
    const sorted = [...values].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    const groups = [];

    for (const value of sorted) {
        if (value.group_label) {
            const existing = groups.find((group) => group.group_label === value.group_label);

            if (existing) {
                existing.fields.push(value);
                continue;
            }

            groups.push({
                group_label: value.group_label,
                label: value.group_label,
                is_multi_value: true,
                fields: [value],
            });
            continue;
        }

        groups.push({
            group_label: null,
            label: value.field_label,
            is_multi_value: false,
            fields: [value],
        });
    }

    return groups;
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

function parseDefaultValues(defaultValuesText, legacyDefaultValue = '') {
    const fromText = parseOptions(defaultValuesText);

    if (fromText.length) {
        return fromText;
    }

    if (legacyDefaultValue?.trim()) {
        return [legacyDefaultValue.trim()];
    }

    return [];
}
