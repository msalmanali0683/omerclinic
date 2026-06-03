import { reactive } from 'vue';

export function useFormErrors() {
    const errors = reactive({});

    function clearErrors() {
        Object.keys(errors).forEach((k) => delete errors[k]);
    }

    function setErrors(error) {
        clearErrors();
        const data = error.response?.data;
        if (data?.errors) {
            for (const [key, messages] of Object.entries(data.errors)) {
                errors[key] = messages[0];
            }
        }
    }

    return { errors, setErrors, clearErrors };
}
