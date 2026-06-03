export const HOSPITAL_DISPLAY_NAME = 'The Omer Clinic';

export const LAB_REPORT_FOOTER_LINES = [
  'Not valid for court.',
  'This is a computer-generated laboratory report.',
];

export function resolveHospitalName(printData) {
  return printData?.hospital_name || HOSPITAL_DISPLAY_NAME;
}

export function resolveLabReportFooterLines(printData) {
  const lines = printData?.report_footer_lines;
  if (Array.isArray(lines) && lines.length) {
    return lines;
  }

  return LAB_REPORT_FOOTER_LINES;
}
