import { defineStore } from 'pinia';
import { authService } from '@/services/authService';
import { buildAuthUser, hasRole, hasAnyRole, can, canAny, canAll } from '@/utils/permissions';

function applyAuthPayload(state, data) {
    const roles = data.roles ?? data.user?.roles ?? [];
    const permissions = data.permissions ?? data.user?.permissions ?? [];

    state.user = buildAuthUser(data.user ?? null, roles, permissions);
    state.roles = state.user?.roles ?? [];
    state.permissions = state.user?.permissions ?? [];
}

let fetchUserPromise = null;

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        roles: [],
        permissions: [],
        loading: false,
        initialized: false,
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
            } catch {
                try {
                    await authService.csrf();
                    const { data } = await authService.me();
                    applyAuthPayload(this, data);
                } catch {
                    this.user = null;
                    this.roles = [];
                    this.permissions = [];
                }
            } finally {
                this.loading = false;
                this.initialized = true;
            }
        },

        async refreshUser() {
            if (!this.user) {
                return;
            }

            try {
                const { data } = await authService.me();
                applyAuthPayload(this, data);
            } catch {
                // Keep the current session on transient failures (do not force logout).
            }
        },

        /** Returns true when /api/me succeeds (session still valid). */
        async confirmSessionAlive() {
            try {
                const { data } = await authService.me();
                applyAuthPayload(this, data);

                return true;
            } catch {
                this.clearUser();

                return false;
            }
        },

        setUser(user) {
            applyAuthPayload(this, { user });
        },

        async login(credentials) {
            this.loading = true;
            try {
                const { data } = await authService.login(credentials);
                applyAuthPayload(this, data);
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
            this.user = null;
            this.roles = [];
            this.permissions = [];
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
