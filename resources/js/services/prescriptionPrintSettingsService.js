import api from '@/services/api';

export const prescriptionPrintSettingsService = {
  getSettings: () => api.get('/prescription-print-settings'),
  updateSettings: (data) => api.put('/prescription-print-settings', data),
};
