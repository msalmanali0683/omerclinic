const fieldBase = 'w-full rounded-xl border px-3 py-2.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm transition-colors focus:outline-none focus:ring-2';

const colorMap = {
  emerald: {
    card: 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-800/70 dark:from-emerald-950/40 dark:to-gray-900/40',
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
    label: 'text-emerald-800 dark:text-emerald-200',
    hint: 'text-emerald-600/80 dark:text-emerald-300/80',
    input: `${fieldBase} border-emerald-200 dark:border-emerald-800 focus:border-emerald-400 focus:ring-emerald-200 dark:focus:ring-emerald-900/40`,
    section: 'from-emerald-600 via-teal-600 to-cyan-600',
    sectionSoft: 'border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-teal-950/20',
  },
  teal: {
    card: 'border-teal-200 bg-gradient-to-br from-teal-50 to-white dark:border-teal-800/70 dark:from-teal-950/40 dark:to-gray-900/40',
    badge: 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-200',
    label: 'text-teal-800 dark:text-teal-200',
    hint: 'text-teal-600/80 dark:text-teal-300/80',
    input: `${fieldBase} border-teal-200 dark:border-teal-800 focus:border-teal-400 focus:ring-teal-200 dark:focus:ring-teal-900/40`,
    section: 'from-teal-600 via-cyan-600 to-sky-600',
    sectionSoft: 'border-teal-100 bg-gradient-to-r from-teal-50 to-cyan-50 dark:border-teal-900/40 dark:from-teal-950/30 dark:to-cyan-950/20',
  },
  violet: {
    card: 'border-violet-200 bg-gradient-to-br from-violet-50 to-white dark:border-violet-800/70 dark:from-violet-950/40 dark:to-gray-900/40',
    badge: 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200',
    label: 'text-violet-800 dark:text-violet-200',
    hint: 'text-violet-600/80 dark:text-violet-300/80',
    input: `${fieldBase} border-violet-200 dark:border-violet-800 focus:border-violet-400 focus:ring-violet-200 dark:focus:ring-violet-900/40`,
    section: 'from-violet-600 via-purple-600 to-fuchsia-600',
    sectionSoft: 'border-violet-100 bg-gradient-to-r from-violet-50 to-purple-50 dark:border-violet-900/40 dark:from-violet-950/30 dark:to-purple-950/20',
  },
  fuchsia: {
    card: 'border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 to-white dark:border-fuchsia-800/70 dark:from-fuchsia-950/40 dark:to-gray-900/40',
    badge: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/50 dark:text-fuchsia-200',
    label: 'text-fuchsia-800 dark:text-fuchsia-200',
    hint: 'text-fuchsia-600/80 dark:text-fuchsia-300/80',
    input: `${fieldBase} border-fuchsia-200 dark:border-fuchsia-800 focus:border-fuchsia-400 focus:ring-fuchsia-200 dark:focus:ring-fuchsia-900/40`,
    section: 'from-fuchsia-600 via-pink-600 to-rose-600',
    sectionSoft: 'border-fuchsia-100 bg-gradient-to-r from-fuchsia-50 to-pink-50 dark:border-fuchsia-900/40 dark:from-fuchsia-950/30 dark:to-pink-950/20',
  },
  blue: {
    card: 'border-blue-200 bg-gradient-to-br from-blue-50 to-white dark:border-blue-800/70 dark:from-blue-950/40 dark:to-gray-900/40',
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200',
    label: 'text-blue-800 dark:text-blue-200',
    hint: 'text-blue-600/80 dark:text-blue-300/80',
    input: `${fieldBase} border-blue-200 dark:border-blue-800 focus:border-blue-400 focus:ring-blue-200 dark:focus:ring-blue-900/40`,
    section: 'from-blue-600 via-indigo-600 to-violet-600',
    sectionSoft: 'border-blue-100 bg-gradient-to-r from-blue-50 to-indigo-50 dark:border-blue-900/40 dark:from-blue-950/30 dark:to-indigo-950/20',
  },
  sky: {
    card: 'border-sky-200 bg-gradient-to-br from-sky-50 to-white dark:border-sky-800/70 dark:from-sky-950/40 dark:to-gray-900/40',
    badge: 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
    label: 'text-sky-800 dark:text-sky-200',
    hint: 'text-sky-600/80 dark:text-sky-300/80',
    input: `${fieldBase} border-sky-200 dark:border-sky-800 focus:border-sky-400 focus:ring-sky-200 dark:focus:ring-sky-900/40`,
    section: 'from-sky-600 via-cyan-600 to-teal-600',
    sectionSoft: 'border-sky-100 bg-gradient-to-r from-sky-50 to-cyan-50 dark:border-sky-900/40 dark:from-sky-950/30 dark:to-cyan-950/20',
  },
  indigo: {
    card: 'border-indigo-200 bg-gradient-to-br from-indigo-50 to-white dark:border-indigo-800/70 dark:from-indigo-950/40 dark:to-gray-900/40',
    badge: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200',
    label: 'text-indigo-800 dark:text-indigo-200',
    hint: 'text-indigo-600/80 dark:text-indigo-300/80',
    input: `${fieldBase} border-indigo-200 dark:border-indigo-800 focus:border-indigo-400 focus:ring-indigo-200 dark:focus:ring-indigo-900/40`,
    section: 'from-indigo-600 via-blue-600 to-cyan-600',
    sectionSoft: 'border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-blue-950/20',
  },
  amber: {
    card: 'border-amber-200 bg-gradient-to-br from-amber-50 to-white dark:border-amber-800/70 dark:from-amber-950/40 dark:to-gray-900/40',
    badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200',
    label: 'text-amber-800 dark:text-amber-200',
    hint: 'text-amber-600/80 dark:text-amber-300/80',
    input: `${fieldBase} border-amber-200 dark:border-amber-800 focus:border-amber-400 focus:ring-amber-200 dark:focus:ring-amber-900/40`,
    section: 'from-amber-500 via-orange-500 to-rose-500',
    sectionSoft: 'border-amber-100 bg-gradient-to-r from-amber-50 to-orange-50 dark:border-amber-900/40 dark:from-amber-950/30 dark:to-orange-950/20',
  },
};

export function getPatientFieldStyle(color) {
  return colorMap[color] || colorMap.emerald;
}

export const patientFormHeaderClass = 'bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600';

export const patientEditHeaderClass = 'bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600';
