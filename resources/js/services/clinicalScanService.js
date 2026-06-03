import api from './api';

export const clinicalScanService = {
    searchQueuePatients: (params) => api.get('/clinical-scans/queue-patients/search', { params }),
    getScans: (params) => api.get('/clinical-scans', { params }),
    getScan: (id) => api.get(`/clinical-scans/${id}`),
    createScan: (payload) => api.post('/clinical-scans', payload),
    updateScan: (id, payload) => api.patch(`/clinical-scans/${id}`, payload),
    deleteScan: (id) => api.delete(`/clinical-scans/${id}`),
    getScansByVisit: (visitId) => api.get(`/patient-visits/${visitId}/clinical-scans`),
    getScansByPatient: (patientId, params) => api.get(`/patients/${patientId}/clinical-scans`, { params }),
    getClinicalScanHistory: (patientId, params) => api.get(`/patients/${patientId}/clinical-scans-history`, { params }),
    getPrintData: (scanId) => api.get(`/clinical-scans/${scanId}/print-data`),
};
