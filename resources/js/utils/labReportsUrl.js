/**
 * Resolve the public lab reports page URL for QR codes and bill footers.
 */
export function resolveLabReportsUrl(printData) {
  if (printData?.lab_reports_url) {
    return printData.lab_reports_url;
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    return `${window.location.origin}/lab-reports`;
  }

  return '/lab-reports';
}

export function resolveLabReportsFooterText(printData) {
  if (printData?.lab_reports_footer_text) {
    return printData.lab_reports_footer_text;
  }

  return 'Scan QR code or open the link below to print your laboratory reports online.';
}
