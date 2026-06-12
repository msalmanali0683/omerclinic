import api from './api';

export const medicineService = {
    getMedicines: (params) => api.get('/medicines', { params }),
    getMedicine: (id) => api.get(`/medicines/${id}`),
    createMedicine: (payload) => api.post('/medicines', payload),
    updateMedicine: (id, payload) => api.put(`/medicines/${id}`, payload),
    deleteMedicine: (id) => api.delete(`/medicines/${id}`),
    getMedicineOptions: (params) => api.get('/medicines/options', { params }),
    findOrCreateMedicine: (payload) => api.post('/medicines/find-or-create', payload),
};
