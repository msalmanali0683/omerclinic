import api from './api';

export const laboratoryBillService = {
    searchPatients: (params) => api.get('/laboratory/patients', { params }),
    createBill: (payload) => api.post('/laboratory/bills', payload),
    getPrintData: (billId) => api.get(`/laboratory/bills/${billId}/print-data`),
};
