import { defineStore } from 'pinia';

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

        error(message) {
            this.show(message, 'error', 6000);
        },

        remove(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },
    },
});
