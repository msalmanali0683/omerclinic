import { defineStore } from 'pinia';
import { checkRouteAccess } from '@/utils/permissions';
import { resolveDefaultRoute } from '@/utils/navigation';

export const useNetworkStore = defineStore('network', {
    state: () => ({
        isOffline: false,
        showModal: false,
        pendingRecoveryPath: null,
        recoveryInProgress: false,
    }),

    actions: {
        markOffline() {
            this.isOffline = true;
            this.showModal = true;
        },

        markOnline() {
            this.isOffline = false;
            this.showModal = false;
        },

        closeModal() {
            this.showModal = false;
        },

        rememberRecoveryPath(path) {
            if (path && path !== '/403' && path !== '/login') {
                this.pendingRecoveryPath = path;
            }
        },

        clearRecoveryPath() {
            this.pendingRecoveryPath = null;
        },

        async recoverSession(router) {
            if (this.recoveryInProgress) {
                return false;
            }

            this.recoveryInProgress = true;

            try {
                const { useAuthStore } = await import('@/stores/auth');
                const authStore = useAuthStore();
                const restored = await authStore.refreshUser({ allowCachedOnNetworkError: true });

                if (!restored) {
                    return false;
                }

                this.markOnline();

                if (!router) {
                    return true;
                }

                let target = resolveDefaultRoute(authStore.user);

                if (this.pendingRecoveryPath) {
                    try {
                        const resolved = router.resolve(this.pendingRecoveryPath);

                        if (
                            resolved.matched.length
                            && resolved.name !== 'login'
                            && resolved.name !== 'not-found'
                            && checkRouteAccess(resolved.meta, authStore.user)
                        ) {
                            target = { path: this.pendingRecoveryPath };
                        }
                    } catch {
                        // Keep default route fallback.
                    }
                }

                this.clearRecoveryPath();
                await router.replace(target);

                return true;
            } finally {
                this.recoveryInProgress = false;
            }
        },
    },
});
