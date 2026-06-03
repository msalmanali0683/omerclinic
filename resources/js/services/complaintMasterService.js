import api from './api';

export const complaintMasterService = {
    getComplaints: (params) => api.get('/complaint-masters', { params }),
    getComplaint: (id) => api.get(`/complaint-masters/${id}`),
    createComplaint: (payload) => api.post('/complaint-masters', payload),
    updateComplaint: (id, payload) => api.put(`/complaint-masters/${id}`, payload),
    deleteComplaint: (id) => api.delete(`/complaint-masters/${id}`),
    getComplaintOptions: (params) => api.get('/complaint-masters/options', { params }),
    findOrCreateComplaint: (payload) => api.post('/complaint-masters/find-or-create', payload),
};
