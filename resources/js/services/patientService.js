import api from './api';

export const patientService = {
    getPatients: (params) => api.get('/patients', { params }),
    searchPatients: (q) => api.get('/patients/search', { params: { q } }),
    searchPatientVisits: (params) => api.get('/patient-visits/search', { params }),
    getPatient: (id) => api.get(`/patients/${id}`),
    createPatient: (payload) => api.post('/patients', payload),
    updatePatient: (id, payload) => api.put(`/patients/${id}`, payload),
    deletePatient: (id) => api.delete(`/patients/${id}`),
};
