import {
    getApiErrorMessage,
    isNetworkError,
    isServerError,
    NETWORK_ERROR_MESSAGE,
    SERVER_ERROR_MESSAGE,
} from '../resources/js/utils/apiErrors.js';

let passed = 0;
let failed = 0;

function assert(condition, label) {
    if (condition) {
        passed += 1;
        return;
    }

    failed += 1;
    console.error(`FAIL: ${label}`);
}

assert(isNetworkError({ code: 'ERR_NETWORK' }), 'ERR_NETWORK is network error');
assert(isNetworkError({ request: {}, message: 'Network Error' }), 'request without response is network error');
assert(!isNetworkError({ response: { status: 500 } }), '500 response is not network error');
assert(getApiErrorMessage({ code: 'ERR_NETWORK' }) === NETWORK_ERROR_MESSAGE, 'network message');
assert(getApiErrorMessage({ response: { status: 500 } }) === SERVER_ERROR_MESSAGE, 'server message');
assert(
    getApiErrorMessage({ response: { status: 422, data: { message: 'Validation failed' } } }) === 'Validation failed',
    'api message preserved',
);
assert(getApiErrorMessage({}, 'Fallback') === 'Fallback', 'fallback message');
assert(isServerError({ response: { status: 503 } }), '503 is server error');
assert(!isServerError({ response: { status: 403 } }), '403 is not server error');

console.log(`apiErrors tests: ${passed} passed, ${failed} failed`);
process.exit(failed > 0 ? 1 : 0);
