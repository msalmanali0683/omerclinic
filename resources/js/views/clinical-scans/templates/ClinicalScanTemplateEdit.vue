<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit Scan Template</h2>
    <div v-if="pageLoading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />
    <ClinicalScanTemplateForm
      v-else
      :initial-form="initialForm"
      submit-label="Update Template"
      :submitting="saving"
      :errors="errors"
      @submit="submit"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { useFormErrors } from '@/composables/useFormErrors';
import ClinicalScanTemplateForm from '@/components/clinical-scans/ClinicalScanTemplateForm.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();
const saving = ref(false);
const pageLoading = ref(true);
const initialForm = ref(null);

async function submit(payload) {
  clearErrors();
  saving.value = true;
  try {
    await clinicalScanTemplateService.updateTemplate(route.params.id, payload);
    toastStore.success('Template updated.');
    router.push('/clinical-scans/templates');
  } catch (e) {
    setErrors(e);
    const status = e.response?.status;
    const message = status === 403
      ? (e.response?.data?.message ?? 'You are not authorized to update this scan template.')
      : (e.response?.data?.message ?? 'Update failed.');
    toastStore.error(message);
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await clinicalScanTemplateService.getTemplate(route.params.id);
    initialForm.value = data.data ?? data;
  } catch {
    toastStore.error('Failed to load template.');
    router.push('/clinical-scans/templates');
  } finally {
    pageLoading.value = false;
  }
});
</script>
