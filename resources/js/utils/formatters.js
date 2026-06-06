export const APP_TIMEZONE = 'Asia/Karachi';
export const APP_LOCALE = 'en-PK';

export const GENDER_OPTIONS = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];

export const AGE_UNIT_OPTIONS = [
    { value: 'years', label: 'Years' },
    { value: 'months', label: 'Months' },
    { value: 'days', label: 'Days' },
];

export function formatGender(value) {
    const map = {
        male: 'Male',
        female: 'Female',
        other: 'Other',
    };

    return map[value] || 'N/A';
}

export function formatAge(age, unit = 'years') {
    if (age === null || age === undefined || age === '') {
        return 'N/A';
    }

    const unitMap = {
        years: 'Years',
        months: 'Months',
        days: 'Days',
    };

    return `${age} ${unitMap[unit] || 'Years'}`;
}

export function displayPatientAge(patient) {
    if (!patient) {
        return 'N/A';
    }

    if (patient.patient_age_display) {
        return patient.patient_age_display;
    }

    return formatAge(patient.patient_age, patient.patient_age_unit);
}

function parseDate(value) {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(value, fallback = '—') {
    const date = parseDate(value);
    if (!date) {
        return fallback;
    }

    return new Intl.DateTimeFormat(APP_LOCALE, {
        timeZone: APP_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
}

export function formatDateTime(value, fallback = '—') {
    const date = parseDate(value);
    if (!date) {
        return fallback;
    }

    return new Intl.DateTimeFormat(APP_LOCALE, {
        timeZone: APP_TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
}

function getDatePartsInTimezone(date, timeZone) {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone,
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
        hour12: false,
    }).formatToParts(date);

    const get = (type) => parts.find((part) => part.type === type)?.value ?? '';

    return {
        day: get('day'),
        month: get('month'),
        year: get('year'),
        hour: get('hour'),
        minute: get('minute'),
        second: get('second'),
    };
}

export function formatVisitTime(value, fallback = '—') {
    if (!value) {
        return fallback;
    }

    const timeValue = typeof value === 'string' && value.includes('T')
        ? parseDate(value)
        : parseDate(`1970-01-01T${value}`);

    if (!timeValue) {
        return String(value);
    }

    return new Intl.DateTimeFormat(APP_LOCALE, {
        timeZone: APP_TIMEZONE,
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(timeValue);
}

export function formatPrescriptionDateTime(prescription, visit) {
    const dateSource = prescription?.prescription_date || visit?.visit_date;
    const timeSource = visit?.visit_time || prescription?.created_at;

    if (!dateSource) {
        return '—';
    }

    const date = parseDate(dateSource);
    if (!date) {
        return String(dateSource);
    }

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const dateParts = getDatePartsInTimezone(date, APP_TIMEZONE);
    const day = String(dateParts.day).padStart(2, '0');
    const month = months[Number(dateParts.month) - 1] ?? dateParts.month;
    const year = dateParts.year;

    let timePart = '';
    if (timeSource) {
        const timeValue = typeof timeSource === 'string' && timeSource.includes('T')
            ? parseDate(timeSource)
            : parseDate(`1970-01-01T${timeSource}`);

        if (timeValue) {
            const timeParts = getDatePartsInTimezone(timeValue, APP_TIMEZONE);
            timePart = ` ${String(timeParts.hour).padStart(2, '0')}:${String(timeParts.minute).padStart(2, '0')}:${String(timeParts.second).padStart(2, '0')}`;
        }
    }

    return `${day}-${month}-${year}${timePart}`;
}

/** Pakistani CNIC mask: XXXXX-XXXXXXX-X */
export function formatCnicInput(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 13);

    if (digits.length <= 5) {
        return digits;
    }

    if (digits.length <= 12) {
        return `${digits.slice(0, 5)}-${digits.slice(5)}`;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12)}`;
}

export function formatCurrency(amount, currency = 'PKR') {
    if (amount === null || amount === undefined || amount === '') {
        return '—';
    }

    const value = Number(amount);

    if (Number.isNaN(value)) {
        return '—';
    }

    return new Intl.NumberFormat(APP_LOCALE, {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
}
