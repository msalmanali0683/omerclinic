import axios from 'axios';

/**
 * Read Laravel's XSRF-TOKEN cookie (set by GET /sanctum/csrf-cookie).
 * Prefer this over the meta tag — the meta value goes stale after logout
 * or session()->regenerateToken() while the SPA stays mounted.
 */
export function getXsrfTokenFromCookie() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : null;
}

export function syncCsrfMetaTag() {
    const token = getXsrfTokenFromCookie();
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (token && meta) {
        meta.setAttribute('content', token);
    }
    return token;
}

export function applyCsrfHeaders(config) {
    const token = getXsrfTokenFromCookie();
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
        delete config.headers['X-CSRF-TOKEN'];
    }
    return config;
}

export async function refreshCsrfCookie() {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
    syncCsrfMetaTag();
}
