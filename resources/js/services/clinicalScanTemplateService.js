import api from './api';

export const clinicalScanTemplateService = {
    getTemplates: (params) => api.get('/clinical-scan-templates', { params }),
    getTemplate: (id) => api.get(`/clinical-scan-templates/${id}`),
    createTemplate: (payload) => api.post('/clinical-scan-templates', payload),
    updateTemplate: (id, payload) => api.patch(`/clinical-scan-templates/${id}`, payload),
    deleteTemplate: (id) => api.delete(`/clinical-scan-templates/${id}`),
    getTemplateOptions: () => api.get('/clinical-scan-templates/options'),
};
