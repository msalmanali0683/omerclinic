import api from './api';

export const diagnosisMasterService = {
    getDiagnoses: (params) => api.get('/diagnosis-masters', { params }),
    getDiagnosis: (id) => api.get(`/diagnosis-masters/${id}`),
    createDiagnosis: (payload) => api.post('/diagnosis-masters', payload),
    updateDiagnosis: (id, payload) => api.put(`/diagnosis-masters/${id}`, payload),
    deleteDiagnosis: (id) => api.delete(`/diagnosis-masters/${id}`),
    getDiagnosisOptions: (params) => api.get('/diagnosis-masters/options', { params }),
    findOrCreateDiagnosis: (payload) => api.post('/diagnosis-masters/find-or-create', payload),
};
