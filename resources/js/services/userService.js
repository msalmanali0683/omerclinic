import api from './api';

export const userService = {
    list: (params) => api.get('/users', { params }),
    listDoctors: () => api.get('/doctors'),
    get: (id) => api.get(`/users/${id}`),
    create: (data) => api.post('/users', data),
    update: (id, data) => api.put(`/users/${id}`, data),
    remove: (id) => api.delete(`/users/${id}`),
    syncRoles: (id, roles) => api.post(`/users/${id}/roles`, { roles }),
    syncPermissions: (id, permissions) => api.post(`/users/${id}/permissions`, { permissions }),
};
