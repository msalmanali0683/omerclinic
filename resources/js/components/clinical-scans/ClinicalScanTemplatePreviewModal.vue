<template>
  <BaseModal
    :model-value="modelValue"
    :title="modalTitle"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
      Preview uses sample patient details and demo values from each field&apos;s defaults.
    </p>

    <div v-if="loading" class="h-64 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-700" />

    <div
      v-else-if="printData"
      class="preview-shell max-h-[70vh] overflow-auto rounded-lg border border-gray-300 bg-gray-100 p-3 sm:p-4 dark:border-gray-600 dark:bg-gray-950"
    >
      <div class="mx-auto bg-white shadow-sm" :style="previewFrameStyle">
        <ClinicalScanPrintPreview :print-data="printData" />
      </div>
    </div>

    <p v-else class="text-sm text-gray-500 dark:text-gray-400">Unable to load preview.</p>

    <template #footer>
      <BaseButton variant="secondary" @click="$emit('update:modelValue', false)">Close</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useToastStore } from '@/stores/toast';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { buildClinicalScanTemplatePreviewPrintData } from '@/utils/clinicalScanTemplatePreview';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import ClinicalScanPrintPreview from '@/components/clinical-scans/ClinicalScanPrintPreview.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  templateId: { type: [Number, String, null], default: null },
  templateName: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const toastStore = useToastStore();
const loading = ref(false);
const printData = ref(null);

const modalTitle = computed(() => {
  const name = props.templateName?.trim();

  return name ? `Preview: ${name}` : 'Scan Template Preview';
});

const previewFrameStyle = {
  width: '210mm',
  maxWidth: '100%',
};

async function loadPreview(templateId) {
  if (!templateId) {
    printData.value = null;

    return;
  }

  loading.value = true;
  printData.value = null;

  try {
    const { data } = await clinicalScanTemplateService.getTemplate(templateId);
    const template = data.data ?? data;

    printData.value = buildClinicalScanTemplatePreviewPrintData(template);
  } catch (error) {
    toastStore.error(error.response?.data?.message ?? 'Failed to load template preview.');
    printData.value = null;
  } finally {
    loading.value = false;
  }
}

watch(
  () => [props.modelValue, props.templateId],
  ([open, templateId]) => {
    if (open && templateId) {
      loadPreview(templateId);
    }
  },
);
</script>
