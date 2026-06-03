/** Predefined medicine types for combobox selection */
export const MEDICINE_TYPE_OPTIONS = [
    { value: 'Tablet', label: 'Tablet' },
    { value: 'Capsule', label: 'Capsule' },
    { value: 'Syrup', label: 'Syrup' },
    { value: 'Injection', label: 'Injection' },
    { value: 'Inj', label: 'Inj' },
    { value: 'Mix', label: 'Mix' },
    { value: 'Cream', label: 'Cream' },
    { value: 'Drops', label: 'Drops' },
    { value: 'Inhaler', label: 'Inhaler' },
    { value: 'Sachet', label: 'Sachet' },
];

export function medicineTypeOptionsFor(currentType = '') {
    const options = [...MEDICINE_TYPE_OPTIONS];
    const value = (currentType ?? '').trim();
    if (value && !options.some((o) => o.value === value)) {
        options.push({ value, label: value });
    }
    return options;
}
