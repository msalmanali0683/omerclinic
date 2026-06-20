export const NETWORK_ERROR_MESSAGE = 'Network connection problem. Please check your internet and try again.';

export const SERVER_ERROR_MESSAGE = 'Server error. Please try again later.';

export function isNetworkError(error) {
    if (!error) {
        return false;
    }

    if (error.isNetworkError === true) {
        return true;
    }

    const code = error.code ?? '';

    if (code === 'ERR_NETWORK' || code === 'ECONNABORTED' || code === 'ETIMEDOUT') {
        return true;
    }

    if (!error.response && Boolean(error.request)) {
        return true;
    }

    const message = String(error.message ?? '').toLowerCase();

    return message.includes('network error') || message.includes('network request failed');
}

export function isServerError(error) {
    const status = Number(error?.response?.status);

    return Number.isInteger(status) && status >= 500;
}

export function getApiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
    if (isNetworkError(error)) {
        return NETWORK_ERROR_MESSAGE;
    }

    const message = error?.response?.data?.message;

    if (typeof message === 'string' && message.trim() !== '') {
        return message;
    }

    if (isServerError(error)) {
        return SERVER_ERROR_MESSAGE;
    }

    return fallback;
}

export function isAxiosLikeError(value) {
    return Boolean(
        value
        && typeof value === 'object'
        && (value.isAxiosError || value.response || value.request || value.config),
    );
}
