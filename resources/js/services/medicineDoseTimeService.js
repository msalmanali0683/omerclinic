import api from './api';

export const medicineDoseTimeService = {
    getDoseTimes: (params) => api.get('/medicine-dose-times', { params }),
    getDoseTime: (id) => api.get(`/medicine-dose-times/${id}`),
    createDoseTime: (payload) => api.post('/medicine-dose-times', payload),
    updateDoseTime: (id, payload) => api.put(`/medicine-dose-times/${id}`, payload),
    deleteDoseTime: (id) => api.delete(`/medicine-dose-times/${id}`),
    getDoseTimeOptions: () => api.get('/medicine-dose-times/options'),
};
