import axios from 'axios';
import { applyCsrfHeaders, refreshCsrfCookie } from '@/utils/csrf';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

api.interceptors.request.use((config) => applyCsrfHeaders(config));

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const config = error.config;

        if (status === 419 && config && !config._csrfRetried) {
            config._csrfRetried = true;
            try {
                await refreshCsrfCookie();
                applyCsrfHeaders(config);
                return api(config);
            } catch {
                return Promise.reject(error);
            }
        }

        if (status === 401) {
            const url = config?.url ?? '';
            if (!url.includes('/me') && !url.includes('/login')) {
                const { useAuthStore } = await import('@/stores/auth');
                const authStore = useAuthStore();
                authStore.clearUser();
                if (!window.location.pathname.startsWith('/login')) {
                    window.location.href = '/login';
                }
            }
        }

        return Promise.reject(error);
    }
);

export default api;
