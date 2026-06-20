import { reactive } from 'vue';
import { getApiErrorMessage, isAxiosLikeError } from '@/utils/apiErrors';

export function useFormErrors() {
    const errors = reactive({});

    function clearErrors() {
        Object.keys(errors).forEach((k) => delete errors[k]);
    }

    function setErrors(error, fallback = 'Something went wrong. Please try again.') {
        clearErrors();
        const data = error?.response?.data;

        if (data?.errors) {
            for (const [key, messages] of Object.entries(data.errors)) {
                errors[key] = messages[0];
            }

            return;
        }

        if (isAxiosLikeError(error)) {
            errors.general = getApiErrorMessage(error, fallback);
        }
    }

    return { errors, setErrors, clearErrors };
}
