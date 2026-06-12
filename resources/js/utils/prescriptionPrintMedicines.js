export function isInjectionMedicine(medicine) {
    const type = String(medicine?.mdcn_type || '')
        .trim()
        .toLowerCase()
        .replace('.', '');

    return type === 'inj' || type === 'injection';
}

export function printMedicineTypeLabel(type) {
    const value = String(type ?? '').trim();

    if (value === '') {
        return '';
    }

    const key = value.toLowerCase().replace(/\.$/, '');

    if (key === 'mix') {
        return '';
    }

    return value;
}

export function formatMedicineLineForPrint(medicine) {
    const parts = [
        printMedicineTypeLabel(medicine?.mdcn_type),
        medicine?.mdcn_name,
        medicine?.mdcn_size,
    ].filter(Boolean);

    return parts.join(' ').trim() || '—';
}

export function shouldShowInTreatmentGiven(medicine) {
    if (medicine?.show_in_treatment_given === true) {
        return true;
    }

    if (medicine?.show_in_treatment_given === false) {
        return false;
    }

    return isInjectionMedicine(medicine);
}

export function splitPrintMedicines(medicines = []) {
    const list = Array.isArray(medicines) ? medicines : [];

    return {
        regularMedicines: list.filter((medicine) => !shouldShowInTreatmentGiven(medicine)),
        injectionMedicines: list.filter((medicine) => shouldShowInTreatmentGiven(medicine)),
    };
}
