import { formatPrescriptionDateTime } from '@/utils/formatters';
import { PRESCRIPTION_PRINT_FONT_FAMILY } from '@/utils/prescriptionPrintFonts';

export { PRESCRIPTION_PRINT_FONT_FAMILY };

export const PRESCRIPTION_PRINT_PAGE_SIZE = 'A4';
export const PRESCRIPTION_PRINT_PAGE_ORIENTATION = 'portrait';
export const PRESCRIPTION_PRINT_PAGE_DIMENSIONS = {
    width: '210mm',
    minHeight: '297mm',
};

export const PRESCRIPTION_PRINT_SETTINGS = {
    pageSize: PRESCRIPTION_PRINT_PAGE_SIZE,
    orientation: PRESCRIPTION_PRINT_PAGE_ORIENTATION,
    margin: '0.1in 0.32in 0.2in 0.5in',
    marginTop: '0.1in',
    marginRight: '0.32in',
    marginBottom: '0.2in',
    marginLeft: '0.5in',
    fontSize: 12,
    letterheadHeight: '2.45in',
    showComplaints: true,
    showVitals: true,
    showDiagnosis: false,
};

export const NEXT_VISIT_DAY_OPTIONS = [
    { value: '', label: 'No follow-up' },
    { value: 1, label: '1 Day' },
    { value: 2, label: '2 Days' },
    { value: 3, label: '3 Days' },
    { value: 5, label: '5 Days' },
    { value: 7, label: '7 Days' },
    { value: 10, label: '10 Days' },
    { value: 14, label: '14 Days' },
    { value: 15, label: '15 Days' },
    { value: 30, label: '30 Days' },
];

export function applyPrescriptionPrintPageStyle(settings = PRESCRIPTION_PRINT_SETTINGS) {
    let el = document.getElementById('dynamic-print-page-style');
    if (!el) {
        el = document.createElement('style');
        el.id = 'dynamic-print-page-style';
        document.head.appendChild(el);
    }

    el.textContent = `
        @media print {
            @page {
                size: ${settings.pageSize} ${settings.orientation};
                margin: ${settings.margin};
            }
        }
    `;
}

export { formatPrescriptionDateTime };

export function formatMedicineLine(medicine) {
    const parts = [medicine?.mdcn_type, medicine?.mdcn_name, medicine?.mdcn_size].filter(Boolean);
    return parts.join(' ').trim() || '—';
}
