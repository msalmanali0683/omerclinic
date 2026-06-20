import axios from 'axios';
import { applyCsrfHeaders, refreshCsrfCookie } from '@/utils/csrf';
import { capitalizePayload } from '@/utils/textCase';
import { isNetworkError } from '@/utils/apiErrors';
import { useNetworkStore } from '@/stores/network';

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    applyCsrfHeaders(config);

    const method = config.method?.toLowerCase();

    if (['post', 'put', 'patch'].includes(method) && config.data && typeof config.data === 'object' && !(config.data instanceof FormData)) {
        const url = config.url ?? '';

        if (!url.includes('/roles') && !url.includes('/permissions')) {
            config.data = capitalizePayload(config.data);
        }
    }

    return config;
});

api.interceptors.response.use(
    (response) => {
        useNetworkStore().markOnline();

        return response;
    },
    async (error) => {
        if (isNetworkError(error)) {
            error.isNetworkError = true;
            useNetworkStore().markOffline();

            return Promise.reject(error);
        }

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

            if (url.includes('/me') || url.includes('/login') || url.includes('/logout')) {
                return Promise.reject(error);
            }

            if (config && !config._authRetried) {
                config._authRetried = true;
                try {
                    await refreshCsrfCookie();
                    applyCsrfHeaders(config);
                    return api(config);
                } catch (retryError) {
                    if (isNetworkError(retryError)) {
                        error.isNetworkError = true;
                        useNetworkStore().markOffline();

                        return Promise.reject(retryError);
                    }

                    if (retryError.response?.status !== 401) {
                        return Promise.reject(retryError);
                    }
                }
            }

            const { useAuthStore } = await import('@/stores/auth');
            const authStore = useAuthStore();
            const alive = await authStore.confirmSessionAlive();

            const publicPaths = ['/login', '/lab-reports'];
            const isPublicPath = publicPaths.some((path) => window.location.pathname.startsWith(path));

            if (!alive && !isPublicPath) {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    },
);

export default api;
