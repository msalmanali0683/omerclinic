import api from './api';

export const prescriptionService = {
    getPrescriptions: (params) => api.get('/prescriptions', { params }),
    getPrescription: (id) => api.get(`/prescriptions/${id}`),
    getPrescriptionByVisit: (visitId) => api.get(`/patient-visits/${visitId}/prescription`),
    createPrescription: (payload) => api.post('/prescriptions', payload),
    updatePrescription: (id, payload) => api.patch(`/prescriptions/${id}`, payload),
    updatePrescriptionByVisit: (visitId, payload) => api.patch(`/patient-visits/${visitId}/prescription`, payload),
    deletePrescription: (id) => api.delete(`/prescriptions/${id}`),
    getPrescriptionCreateData: (visitId) => api.get(`/patient-visits/${visitId}/prescription-create-data`),
    getPrintData: (prescriptionId) => api.get(`/prescriptions/${prescriptionId}/print-data`),
    create: (payload) => api.post('/prescriptions', payload),
};
