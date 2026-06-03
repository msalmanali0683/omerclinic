import api from './api';

export const permissionService = {
    list: () => api.get('/permissions'),
    create: (data) => api.post('/permissions', data),
    update: (id, data) => api.put(`/permissions/${id}`, data),
    remove: (id) => api.delete(`/permissions/${id}`),
};
