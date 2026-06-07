import api from './api';

export const patientQueueService = {
    getQueue: (params) => api.get('/patient-queue', { params }),
    addToQueue: (patientId, payload) => api.post(`/patients/${patientId}/add-to-queue`, payload),
    getQueueItem: (visitId) => api.get(`/patient-queue/${visitId}`),
    assignDoctor: (visitId, payload) => api.patch(`/patient-queue/${visitId}/assign-doctor`, payload),
    startConsultation: (visitId) => api.patch(`/patient-queue/${visitId}/start-consultation`),
    markPrescribed: (visitId) => api.patch(`/patient-queue/${visitId}/mark-prescribed`),
    returnToPendingPrescription: (visitId) => api.patch(`/patient-queue/${visitId}/return-to-pending-prescription`),
    cancelQueue: (visitId) => api.patch(`/patient-queue/${visitId}/cancel`),
    cancelStaleQueue: () => api.post('/patient-queue/cancel-stale'),
};
