export function buildTokenPrintDataFromResponse(data) {
  if (data?.print_data) {
    return data.print_data;
  }

  return {
    patient_name: data?.patient?.patient_name ?? '',
    patient_father_name: data?.patient?.patient_father_name ?? '',
    mr_number: data?.patient?.mr_number ?? '',
    token_number: data?.token?.token_number ?? '',
    token_display: data?.token?.token_display ?? '',
    token_date: data?.token?.token_date ?? '',
    visit_date: data?.visit?.visit_date ?? '',
    visit_time: data?.visit?.visit_time ?? '',
  };
}

export function shouldOpenTokenPrintModal(data) {
  return Boolean(data?.print_token && data?.token);
}
