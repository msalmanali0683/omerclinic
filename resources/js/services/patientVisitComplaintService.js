import api from './api';

export const patientVisitComplaintService = {
    getVisitComplaints: (visitId) => api.get(`/patient-visits/${visitId}/complaints`),
    createVisitComplaint: (payload) => api.post('/patient-visit-complaints', payload),
    updateVisitComplaint: (id, payload) => api.put(`/patient-visit-complaints/${id}`, payload),
    deleteVisitComplaint: (id) => api.delete(`/patient-visit-complaints/${id}`),
};
