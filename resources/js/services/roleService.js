import api from './api';

export const roleService = {
    list: () => api.get('/roles'),
    get: (id) => api.get(`/roles/${id}`),
    create: (data) => api.post('/roles', data),
    update: (id, data) => api.put(`/roles/${id}`, data),
    remove: (id) => api.delete(`/roles/${id}`),
    syncPermissions: (id, permissions) => api.post(`/roles/${id}/permissions`, { permissions }),
};
