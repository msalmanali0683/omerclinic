<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-2 sm:p-4 no-print">
        <div class="fixed inset-0 bg-black/50" @click="handleClose" />
        <div class="relative w-full max-w-3xl max-h-[95vh] bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
          <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700 no-print">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="handleClose">✕</button>
          </div>

          <div class="flex-1 overflow-y-auto px-4 pb-4 pt-0.5 sm:px-6 sm:pb-6 sm:pt-0.5 print-preview-panel">
            <div class="preview-shell overflow-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-950 p-3 sm:p-4">
              <div class="mx-auto bg-white shadow-md" style="width: 210mm; min-height: 297mm; padding: 12mm 10mm;">
                <LaboratoryResultPrintPreview
                  v-if="printData"
                  :print-data="printData"
                  :print-area-id="printAreaId"
                />
              </div>
            </div>
          </div>

          <div class="modal-footer px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 no-print">
            <BaseButton type="button" variant="secondary" @click="handleClose">Close</BaseButton>
            <BaseButton type="button" :loading="printing" @click="handlePrint">Print Report</BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { printLaboratoryReportElement } from '@/utils/printLaboratoryReport';
import LaboratoryResultPrintPreview from '@/components/laboratory/LaboratoryResultPrintPreview.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  printData: { type: Object, default: null },
  title: { type: String, default: 'Laboratory Test Report' },
  printAreaId: { type: String, default: 'laboratory-report-print-area' },
});

const emit = defineEmits(['update:modelValue', 'printed', 'closed']);

const toastStore = useToastStore();
const printing = ref(false);

function handleClose() {
  emit('update:modelValue', false);
  emit('closed');
}

async function handlePrint() {
  printing.value = true;

  try {
    await printLaboratoryReportElement(props.printAreaId, {
      onAfterPrint: () => emit('printed'),
    });
  } catch (error) {
    toastStore.error(error.message ?? 'Unable to print laboratory report.');
  } finally {
    printing.value = false;
  }
}
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
