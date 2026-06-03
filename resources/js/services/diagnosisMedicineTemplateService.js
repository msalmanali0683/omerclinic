import api from './api';

export const diagnosisMedicineTemplateService = {
    getTemplates: (params) => api.get('/diagnosis-medicine-templates', { params }),
    getTemplate: (id) => api.get(`/diagnosis-medicine-templates/${id}`),
    createTemplate: (payload) => api.post('/diagnosis-medicine-templates', payload),
    updateTemplate: (id, payload) => api.put(`/diagnosis-medicine-templates/${id}`, payload),
    deleteTemplate: (id) => api.delete(`/diagnosis-medicine-templates/${id}`),
    getTemplatesByDiagnosis: (diagnosisId) => api.get(`/diagnosis-masters/${diagnosisId}/medicine-templates`),
};
