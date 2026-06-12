import { resolveTemplateDefaultValues } from '@/utils/clinicalScans';
import { getPrescriptionPrintSampleData } from '@/utils/prescriptionPrintSampleData';

function demoValueForField(field) {
    const defaults = resolveTemplateDefaultValues(field);

    if (defaults.length) {
        return defaults[0];
    }

    const label = String(field.field_label || field.group_label || 'Finding').trim();

    if (/impression/i.test(label)) {
        return 'Within normal limits';
    }

    switch (field.field_type) {
        case 'number':
            return '10';
        case 'date':
            return new Date().toISOString().slice(0, 10);
        case 'select':
        case 'checkbox':
            return (Array.isArray(field.options) && field.options[0]) || 'Normal';
        default:
            return `Normal ${label.toLowerCase()}`;
    }
}

export function buildClinicalScanTemplatePreviewPrintData(template) {
    const sample = getPrescriptionPrintSampleData();
    const fields = [...(template?.fields || [])].sort(
        (a, b) => (a.sort_order || 0) - (b.sort_order || 0),
    );

    const values = fields.map((field, index) => ({
        id: `preview-${field.id || index}`,
        field_label: field.field_label,
        group_label: field.group_label || null,
        field_key: field.field_key || `field-${index}`,
        field_value: demoValueForField(field),
        print_in_box: !!field.print_in_box,
        sort_order: field.sort_order || index + 1,
    }));

    return {
        ...sample,
        medicines: [],
        clinical_scans: [
            {
                id: 'template-preview',
                scan_template_name: template?.template_name || 'Scan Template',
                scan_date: sample.visit?.visit_date,
                impression: null,
                values,
            },
        ],
    };
}
