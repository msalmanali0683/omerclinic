import api from './api';

export const reportService = {
    getPatientReport: (params) => api.get('/reports/patients', { params }),
    getPatientReportPrintData: (params) => api.get('/reports/patients/print-data', { params }),
    exportPatientReportPdf: (params) => api.get('/reports/patients/pdf', {
        params,
        responseType: 'blob',
    }),
    getLaboratoryReport: (params) => api.get('/reports/laboratory', { params }),
    getLaboratoryReportPrintData: (params) => api.get('/reports/laboratory/print-data', { params }),
    exportLaboratoryReportPdf: (params) => api.get('/reports/laboratory/pdf', {
        params,
        responseType: 'blob',
    }),
};
