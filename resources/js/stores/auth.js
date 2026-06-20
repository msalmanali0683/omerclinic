import { defineStore } from 'pinia';
import { authService } from '@/services/authService';
import { isNetworkError } from '@/utils/apiErrors';
import { buildAuthUser, hasRole, hasAnyRole, can, canAny, canAll } from '@/utils/permissions';

const SESSION_CACHE_KEY = 'hospital_auth_session';

function applyAuthPayload(state, data) {
    const roles = data.roles ?? data.user?.roles ?? [];
    const permissions = data.permissions ?? data.user?.permissions ?? [];

    state.user = buildAuthUser(data.user ?? null, roles, permissions);
    state.roles = state.user?.roles ?? [];
    state.permissions = state.user?.permissions ?? [];
}

function cacheAuthSession(state) {
    if (!state.user) {
        sessionStorage.removeItem(SESSION_CACHE_KEY);

        return;
    }

    sessionStorage.setItem(SESSION_CACHE_KEY, JSON.stringify({
        user: state.user,
        roles: state.roles,
        permissions: state.permissions,
    }));
}

function restoreAuthSession(state) {
    try {
        const raw = sessionStorage.getItem(SESSION_CACHE_KEY);

        if (!raw) {
            return false;
        }

        const cached = JSON.parse(raw);
        applyAuthPayload(state, cached);

        return Boolean(state.user);
    } catch {
        return false;
    }
}

function clearAuthSession(state) {
    state.user = null;
    state.roles = [];
    state.permissions = [];
    sessionStorage.removeItem(SESSION_CACHE_KEY);
}

let fetchUserPromise = null;

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        roles: [],
        permissions: [],
        loading: false,
        initialized: false,
        networkOffline: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        userRoles: (state) => state.roles,
        userPermissions: (state) => state.permissions,
    },

    actions: {
        async fetchUser() {
            if (fetchUserPromise) {
                return fetchUserPromise;
            }

            fetchUserPromise = this._loadUserFromSession();
            try {
                return await fetchUserPromise;
            } finally {
                fetchUserPromise = null;
            }
        },

        async _loadUserFromSession() {
            this.loading = true;

            try {
                const { data } = await authService.me();
                applyAuthPayload(this, data);
                cacheAuthSession(this);
                this.networkOffline = false;
            } catch (error) {
                try {
                    await authService.csrf();
                    const { data } = await authService.me();
                    applyAuthPayload(this, data);
                    cacheAuthSession(this);
                    this.networkOffline = false;
                } catch (retryError) {
                    if (isNetworkError(error) || isNetworkError(retryError)) {
                        if (restoreAuthSession(this)) {
                            this.networkOffline = true;
                        } else {
                            clearAuthSession(this);
                            this.networkOffline = false;
                        }
                    } else {
                        clearAuthSession(this);
                        this.networkOffline = false;
                    }
                }
            } finally {
                this.loading = false;
                this.initialized = true;
            }
        },

        async refreshUser(options = {}) {
            if (!this.user && !options.allowCachedOnNetworkError) {
                return false;
            }

            try {
                const { data } = await authService.me();
                applyAuthPayload(this, data);
                cacheAuthSession(this);
                this.networkOffline = false;

                return true;
            } catch (error) {
                if (isNetworkError(error)) {
                    if (options.allowCachedOnNetworkError && restoreAuthSession(this)) {
                        this.networkOffline = true;

                        return true;
                    }

                    return Boolean(this.user);
                }

                return false;
            }
        },

        /** Returns true when /api/me succeeds (session still valid). */
        async confirmSessionAlive() {
            try {
                const { data } = await authService.me();
                applyAuthPayload(this, data);
                cacheAuthSession(this);
                this.networkOffline = false;

                return true;
            } catch (error) {
                if (isNetworkError(error) && restoreAuthSession(this)) {
                    this.networkOffline = true;

                    return true;
                }

                clearAuthSession(this);

                return false;
            }
        },

        setUser(user) {
            applyAuthPayload(this, { user });
            cacheAuthSession(this);
        },

        async login(credentials) {
            this.loading = true;
            try {
                const { data } = await authService.login(credentials);
                applyAuthPayload(this, data);
                cacheAuthSession(this);
                this.networkOffline = false;
                this.initialized = true;

                return data;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                await authService.logout();
            } finally {
                this.clearUser();
                await authService.csrf();
            }
        },

        clearUser() {
            clearAuthSession(this);
            this.networkOffline = false;
        },

        hasRole(role) {
            return hasRole(this.user, role);
        },

        hasAnyRole(roles) {
            return hasAnyRole(this.user, roles);
        },

        can(permission) {
            return can(this.user, permission);
        },

        canAny(permissions) {
            return canAny(this.user, permissions);
        },

        canAll(permissions) {
            return canAll(this.user, permissions);
        },
    },
});
