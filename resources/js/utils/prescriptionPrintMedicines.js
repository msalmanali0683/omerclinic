export function isInjectionMedicine(medicine) {
    const type = String(medicine?.mdcn_type || '')
        .trim()
        .toLowerCase()
        .replace('.', '');

    return type === 'inj' || type === 'injection';
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
