import api from './api';

export const patientVisitTokenService = {
    getTokenByVisit(visitId) {
        return api.get(`/patient-visits/${visitId}/token`);
    },

    generateToken(visitId) {
        return api.post(`/patient-visits/${visitId}/token/generate`);
    },

    getTokenPrintData(tokenId) {
        return api.get(`/patient-visit-tokens/${tokenId}/print-data`);
    },

    reprintToken(tokenId) {
        return api.post(`/patient-visit-tokens/${tokenId}/reprint`);
    },
};
