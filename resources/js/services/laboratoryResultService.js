import api from './api';

export const laboratoryResultService = {
    searchPatientVisits: (params) => api.get('/laboratory/patient-visits/search', { params }),
    getResults: (params) => api.get('/laboratory-results', { params }),
    getResult: (id) => api.get(`/laboratory-results/${id}`),
    createResult: (payload) => api.post('/laboratory-results', payload),
    updateResult: (id, payload) => api.patch(`/laboratory-results/${id}`, payload),
    deleteResult: (id) => api.delete(`/laboratory-results/${id}`),
    verifyResult: (id) => api.post(`/laboratory-results/${id}/verify`),
    getResultsByVisit: (visitId) => api.get(`/patient-visits/${visitId}/laboratory-results`),
    getResultsByPatient: (patientId, params) => api.get(`/patients/${patientId}/laboratory-results`, { params }),
    getLaboratoryHistory: (patientId, params) => api.get(`/patients/${patientId}/laboratory-history`, { params }),
    getPrintData: (resultId) => api.get(`/laboratory-results/${resultId}/print-data`),
    getVisitPrintData: (visitId) => api.get(`/patient-visits/${visitId}/laboratory-results/print-data`),
    getPatientsOverview: (params) => api.get('/laboratory-results/patients-overview', { params }),
    getPatientVisitsOverview: (patientId) => api.get(`/laboratory-results/patients/${patientId}/visits-overview`),
    getNoVisitTests: (patientId) => api.get(`/laboratory-results/patients/${patientId}/no-visit-tests`),
    getVisitTests: (patientId, visitId) => api.get(`/laboratory-results/patients/${patientId}/visits/${visitId}/tests`),
};
