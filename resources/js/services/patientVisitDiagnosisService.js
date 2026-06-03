import api from './api';

export const patientVisitDiagnosisService = {
    getVisitDiagnoses: (visitId) => api.get(`/patient-visits/${visitId}/diagnoses`),
    createVisitDiagnosis: (payload) => api.post('/patient-visit-diagnoses', payload),
    updateVisitDiagnosis: (id, payload) => api.put(`/patient-visit-diagnoses/${id}`, payload),
    deleteVisitDiagnosis: (id) => api.delete(`/patient-visit-diagnoses/${id}`),
};
