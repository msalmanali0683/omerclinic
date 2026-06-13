/** Standard medicine types for selects and filters. */

export const MEDICINE_TYPE_OPTIONS = [

    { value: 'Tab.', label: 'Tab.' },

    { value: 'Cap.', label: 'Cap.' },

    { value: 'Syp.', label: 'Syp.' },

    { value: 'Mix.', label: 'Mix.' },

    { value: 'Inj.', label: 'Inj.' },

];



export const MEDICINE_TYPE_FILTER_OPTIONS = [

    { value: '', label: 'All types' },

    ...MEDICINE_TYPE_OPTIONS,

];



/** Select options — always the five standard types only. */

export function medicineTypeOptionsFor() {

    return MEDICINE_TYPE_OPTIONS;

}



export function normalizeMedicineType(type = '') {

    const value = String(type ?? '').trim();

    const key = value.toLowerCase().replace(/\.$/, '');



    switch (key) {

        case 'tab':

        case 'tablet':

        case 'tablets':

            return 'Tab.';

        case 'cap':

        case 'capsule':

        case 'capsules':

            return 'Cap.';

        case 'syp':

        case 'syrup':

        case 'syrups':

            return 'Syp.';

        case 'inj':

        case 'injection':

        case 'injections':

            return 'Inj.';

        case 'mix':

            return 'Mix.';

        default:

            if (MEDICINE_TYPE_OPTIONS.some((option) => option.value === value)) {

                return value;

            }



            return value === '' ? '' : 'Mix.';

    }

}



export function isStandardMedicineType(type = '') {

    const value = (type ?? '').trim();



    return MEDICINE_TYPE_OPTIONS.some((option) => option.value === value);

}


