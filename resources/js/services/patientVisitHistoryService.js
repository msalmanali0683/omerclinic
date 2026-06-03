import api from './api';

export const patientVisitHistoryService = {
    getPatientVisits: (patientId, params) => api.get(`/patients/${patientId}/visits`, { params }),
    getPatientVisitDetails: (patientId, visitId) => api.get(`/patients/${patientId}/visits/${visitId}/details`),
};
