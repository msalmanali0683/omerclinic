import api from './api';
import { refreshCsrfCookie } from '@/utils/csrf';

export const authService = {
    async csrf() {
        await refreshCsrfCookie();
    },

    async login(credentials) {
        await this.csrf();
        return api.post('/login', credentials);
    },

    async logout() {
        return api.post('/logout');
    },

    async me() {
        return api.get('/me');
    },

    async updateProfile(data) {
        return api.patch('/profile', data);
    },
};
