import { defineStore } from 'pinia';
import { getApiErrorMessage, isNetworkError } from '@/utils/apiErrors';

let toastId = 0;

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),

    actions: {
        show(message, type = 'success', duration = 4000) {
            const id = ++toastId;
            this.toasts.push({ id, message, type });
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },

        success(message) {
            this.show(message, 'success');
        },

        error(message, fallback = 'Something went wrong. Please try again.') {
            const resolved = typeof message === 'string' && message.trim() !== ''
                ? message
                : fallback;

            this.show(resolved, 'error', 6000);
        },

        apiError(error, fallback = 'Something went wrong. Please try again.') {
            if (isNetworkError(error)) {
                return;
            }

            this.error(getApiErrorMessage(error, fallback));
        },

        warning(message) {
            this.show(message, 'warning', 5000);
        },

        info(message) {
            this.show(message, 'info', 4000);
        },

        remove(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    },
});
