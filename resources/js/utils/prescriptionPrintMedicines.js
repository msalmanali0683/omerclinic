export function isInjectionMedicine(medicine) {
    const type = String(medicine?.mdcn_type || '')
        .trim()
        .toLowerCase()
        .replace('.', '');

    return type === 'inj' || type === 'injection';
}

export function splitPrintMedicines(medicines = []) {
    const list = Array.isArray(medicines) ? medicines : [];

    return {
        regularMedicines: list.filter((medicine) => !isInjectionMedicine(medicine)),
        injectionMedicines: list.filter((medicine) => isInjectionMedicine(medicine)),
    };
}
