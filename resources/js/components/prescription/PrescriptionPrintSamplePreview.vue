<template>
  <section class="flex min-h-0 flex-col">
    <div class="mb-3 flex shrink-0 flex-wrap items-start justify-between gap-3">
      <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Sample Print Preview</h4>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          Sample data only — shows how vitals, clinical scans, medicines, doses, treatment given, and next visit will look with your current settings.
        </p>
      </div>
      <BaseButton type="button" class="shrink-0" :loading="printing" @click="handlePrintSample">
        Print Sample
      </BaseButton>
    </div>

    <div class="max-h-[75vh] min-h-[420px] flex-1 overflow-auto rounded-lg border border-gray-300 bg-gray-100 p-3 dark:border-gray-600 dark:bg-gray-950">
      <div class="mx-auto bg-white shadow-sm" :style="previewFrameStyle">
        <VisitPrintPreview
          :print-data="samplePrintData"
          :print-settings="printSettings"
          :print-area-id="printAreaId"
          :show-empty-clinical-scans-as-na="false"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useToastStore } from '@/stores/toast';
import { getPrescriptionPrintSampleData } from '@/utils/prescriptionPrintSampleData';
import {
  applyPrescriptionPrintPageStyle,
  getPreviewFrameStyle,
  getPrintElementOptions,
  mergePrescriptionPrintSettings,
} from '@/utils/prescriptionPrintSettings';
import { printPrescriptionElement } from '@/utils/printPrescription';
import VisitPrintPreview from '@/components/prints/VisitPrintPreview.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  printSettings: { type: Object, default: null },
  printAreaId: { type: String, default: 'prescription-print-sample-preview' },
});

const toastStore = useToastStore();
const printing = ref(false);
const samplePrintData = getPrescriptionPrintSampleData();

const resolvedSettings = computed(() => mergePrescriptionPrintSettings(props.printSettings));

const previewFrameStyle = computed(() => getPreviewFrameStyle(resolvedSettings.value));

watch(resolvedSettings, (settings) => {
  applyPrescriptionPrintPageStyle(settings);
}, { immediate: true, deep: true });

async function handlePrintSample() {
  printing.value = true;
  try {
    await printPrescriptionElement(
      props.printAreaId,
      getPrintElementOptions(resolvedSettings.value),
    );
  } catch (error) {
    toastStore.error(error.message || 'Unable to print sample preview.');
  } finally {
    printing.value = false;
  }
}
</script>
