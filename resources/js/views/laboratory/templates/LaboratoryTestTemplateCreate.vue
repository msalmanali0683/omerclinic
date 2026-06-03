<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Create Laboratory Test Template</h2>
    <LaboratoryTestTemplateForm
      submit-label="Create Template"
      :submitting="saving"
      :errors="errors"
      @submit="submit"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { useFormErrors } from '@/composables/useFormErrors';
import LaboratoryTestTemplateForm from '@/components/laboratory/LaboratoryTestTemplateForm.vue';

const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();
const saving = ref(false);

async function submit(payload) {
  clearErrors();
  saving.value = true;
  try {
    await laboratoryTestTemplateService.createTemplate(payload);
    toastStore.success('Laboratory test template created.');
    router.push('/laboratory-results/templates');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
  }
}
</script>
