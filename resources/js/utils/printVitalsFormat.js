const PRINT_VITAL_UNITS = {
  blood_pressure: 'mmHg',
  temperature: '°F',
  pulse_rate: '/Min',
  respiratory_rate: '/Min',
  weight: 'Kg',
};

function hasVitalUnit(value, unit) {
  const normalized = String(value ?? '').trim().toLowerCase();

  if (unit === 'mmHg') {
    return normalized.includes('mmhg');
  }

  if (unit === '°F') {
    return normalized.includes('°f') || normalized.includes(' f') || /\bf\b/.test(normalized);
  }

  if (unit === '/Min') {
    return normalized.includes('/min') || normalized.endsWith('min');
  }

  if (unit === 'Kg') {
    return normalized.includes('kg');
  }

  return normalized.includes(String(unit).toLowerCase());
}

export function formatPrintVitalValue(value, unit) {
  const display = String(value ?? '').trim();

  if (!display || display === 'N/A' || display === '—') {
    return display || 'N/A';
  }

  if (hasVitalUnit(display, unit)) {
    return display;
  }

  return `${display} ${unit}`;
}

export function formatPrintVitals(vitals = {}) {
  return {
    blood_pressure: formatPrintVitalValue(vitals.blood_pressure, PRINT_VITAL_UNITS.blood_pressure),
    temperature: formatPrintVitalValue(vitals.temperature, PRINT_VITAL_UNITS.temperature),
    weight: formatPrintVitalValue(vitals.weight, PRINT_VITAL_UNITS.weight),
    pulse_rate: formatPrintVitalValue(vitals.pulse_rate, PRINT_VITAL_UNITS.pulse_rate),
    respiratory_rate: formatPrintVitalValue(vitals.respiratory_rate, PRINT_VITAL_UNITS.respiratory_rate),
  };
}
