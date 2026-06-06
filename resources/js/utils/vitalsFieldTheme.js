const fieldBase = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm transition-colors focus:outline-none focus:ring-2';

export const vitalFieldMeta = [
  {
    key: 'blood_pressure',
    label: 'B.P',
    title: 'Blood Pressure',
    placeholder: '120/80',
    type: 'text',
    color: 'rose',
  },
  {
    key: 'temperature',
    label: 'Temp',
    title: 'Temperature',
    placeholder: '98.6',
    type: 'number',
    step: '0.1',
    color: 'amber',
  },
  {
    key: 'weight',
    label: 'Weight',
    title: 'Weight (kg)',
    placeholder: 'kg',
    type: 'number',
    step: '0.1',
    color: 'blue',
  },
  {
    key: 'pulse_rate',
    label: 'P/R',
    title: 'Pulse Rate',
    placeholder: '72',
    type: 'number',
    color: 'fuchsia',
  },
  {
    key: 'respiratory_rate',
    label: 'R/R',
    title: 'Respiratory Rate',
    placeholder: '16',
    type: 'number',
    color: 'sky',
  },
];

const colorMap = {
  rose: {
    card: 'border-rose-200 bg-gradient-to-br from-rose-50 to-white dark:border-rose-800/70 dark:from-rose-950/40 dark:to-gray-900/40',
    badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200',
    label: 'text-rose-800 dark:text-rose-200',
    hint: 'text-rose-600/80 dark:text-rose-300/80',
    input: `${fieldBase} border-rose-200 dark:border-rose-800 focus:border-rose-400 focus:ring-rose-200 dark:focus:ring-rose-900/40`,
    value: 'text-rose-900 dark:text-rose-100',
  },
  amber: {
    card: 'border-amber-200 bg-gradient-to-br from-amber-50 to-white dark:border-amber-800/70 dark:from-amber-950/40 dark:to-gray-900/40',
    badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200',
    label: 'text-amber-800 dark:text-amber-200',
    hint: 'text-amber-600/80 dark:text-amber-300/80',
    input: `${fieldBase} border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-200 dark:focus:ring-amber-900/40`,
    value: 'text-amber-900 dark:text-amber-100',
  },
  blue: {
    card: 'border-blue-200 bg-gradient-to-br from-blue-50 to-white dark:border-blue-800/70 dark:from-blue-950/40 dark:to-gray-900/40',
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200',
    label: 'text-blue-800 dark:text-blue-200',
    hint: 'text-blue-600/80 dark:text-blue-300/80',
    input: `${fieldBase} border-blue-200 dark:border-blue-800 focus:border-blue-400 focus:ring-blue-200 dark:focus:ring-blue-900/40`,
    value: 'text-blue-900 dark:text-blue-100',
  },
  fuchsia: {
    card: 'border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-white dark:border-fuchsia-800/70 dark:from-fuchsia-950/40 dark:to-gray-900/40',
    badge: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/50 dark:text-fuchsia-200',
    label: 'text-fuchsia-800 dark:text-fuchsia-200',
    hint: 'text-fuchsia-600/80 dark:text-fuchsia-300/80',
    input: `${fieldBase} border-fuchsia-200 dark:border-fuchsia-800 focus:border-fuchsia-400 focus:ring-fuchsia-200 dark:focus:ring-fuchsia-900/40`,
    value: 'text-fuchsia-900 dark:text-fuchsia-100',
  },
  sky: {
    card: 'border-sky-200 bg-gradient-to-br from-sky-50 to-white dark:border-sky-800/70 dark:from-sky-950/40 dark:to-gray-900/40',
    badge: 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
    label: 'text-sky-800 dark:text-sky-200',
    hint: 'text-sky-600/80 dark:text-sky-300/80',
    input: `${fieldBase} border-sky-200 dark:border-sky-800 focus:border-sky-400 focus:ring-sky-200 dark:focus:ring-sky-900/40`,
    value: 'text-sky-900 dark:text-sky-100',
  },
};

export function getVitalFieldStyle(color) {
  return colorMap[color] || colorMap.rose;
}

export const notesFieldClass = `${fieldBase} border-violet-200 dark:border-violet-800 focus:border-violet-400 focus:ring-violet-200 dark:focus:ring-violet-900/40`;
