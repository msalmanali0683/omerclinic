import api from './api';

export const laboratoryTestTemplateService = {
    getTemplates: (params) => api.get('/laboratory-test-templates', { params }),
    getTemplate: (id) => api.get(`/laboratory-test-templates/${id}`),
    createTemplate: (payload) => api.post('/laboratory-test-templates', payload),
    updateTemplate: (id, payload) => api.patch(`/laboratory-test-templates/${id}`, payload),
    deleteTemplate: (id) => api.delete(`/laboratory-test-templates/${id}`),
    getTemplateOptions: () => api.get('/laboratory-test-templates/options'),
};
