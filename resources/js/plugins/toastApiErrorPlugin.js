import { getApiErrorMessage, isAxiosLikeError, isNetworkError } from '@/utils/apiErrors';
import { useNetworkStore } from '@/stores/network';

export function registerToastApiErrorPlugin(pinia) {
    pinia.use(({ store }) => {
        if (store.$id !== 'toast') {
            return;
        }

        const originalError = store.error.bind(store);

        store.error = (messageOrError, fallback = 'Something went wrong. Please try again.') => {
            const networkStore = useNetworkStore(pinia);

            if (isAxiosLikeError(messageOrError)) {
                if (isNetworkError(messageOrError)) {
                    return;
                }

                originalError(getApiErrorMessage(messageOrError, fallback));

                return;
            }

            if (networkStore.isOffline) {
                return;
            }

            if (typeof messageOrError === 'string' && messageOrError.trim() !== '') {
                originalError(messageOrError);

                return;
            }

            originalError(fallback);
        };

        store.apiError = (error, fallback = 'Something went wrong. Please try again.') => {
            store.error(error, fallback);
        };
    });
}
