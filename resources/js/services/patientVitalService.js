import api from './api';

export const patientVitalService = {
    getVitals: (params) => api.get('/patient-vitals', { params }),
    getVital: (id) => api.get(`/patient-vitals/${id}`),
    createVital: (payload) => api.post('/patient-vitals', payload),
    updateVital: (id, payload) => api.patch(`/patient-vitals/${id}`, payload),
    deleteVital: (id) => api.delete(`/patient-vitals/${id}`),
    getLatestByVisit: (visitId) => api.get(`/patient-visits/${visitId}/vitals/latest`),
    getHistoryByPatient: (patientId, params) => api.get(`/patients/${patientId}/vitals-history`, { params }),
};
