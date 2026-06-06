import api from './api';

export const publicLabReportService = {
    verify: (payload) => api.post('/public/lab-reports/verify', payload),
    getResults: () => api.get('/public/lab-reports/results'),
    getPrintData: (resultId) => api.get(`/public/lab-reports/results/${resultId}/print-data`),
    getAllPrintData: () => api.get('/public/lab-reports/print-data'),
    logout: () => api.post('/public/lab-reports/logout'),
};
