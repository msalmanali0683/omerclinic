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

          <div class="flex-1 overflow-y-auto p-4 sm:p-6 print-preview-panel">
            <div class="preview-shell overflow-auto rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-950 p-3 sm:p-4">
              <div class="mx-auto bg-white shadow-sm" :style="previewFrameStyle">
                <VisitPrintPreview
                  v-if="printData"
                  :print-data="printData"
                  :show-empty-clinical-scans-as-na="showEmptyClinicalScansAsNa"
                  :print-area-id="printAreaId"
                />
              </div>
            </div>
          </div>

          <div class="modal-footer px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 no-print">
            <BaseButton type="button" variant="secondary" @click="handleClose">Close</BaseButton>
            <BaseButton type="button" :loading="printing" @click="handlePrint">Print</BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { PRESCRIPTION_PRINT_SETTINGS, PRESCRIPTION_PRINT_PAGE_DIMENSIONS, applyPrescriptionPrintPageStyle } from '@/utils/prescriptionPrintSettings';
import { printPrescriptionElement } from '@/utils/printPrescription';
import VisitPrintPreview from '@/components/prints/VisitPrintPreview.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  printData: { type: Object, default: null },
  redirectAfterClose: { type: Boolean, default: false },
  redirectTo: { type: [String, Object], default: '' },
  title: { type: String, default: 'Print Preview' },
  printAreaId: { type: String, default: 'prescription-print-area' },
  showEmptyClinicalScansAsNa: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'printed', 'closed']);

const toastStore = useToastStore();
const router = useRouter();
const printing = ref(false);
const hasRedirected = ref(false);

watch(() => props.modelValue, (open) => {
  if (open) {
    hasRedirected.value = false;
    applyPrescriptionPrintPageStyle(PRESCRIPTION_PRINT_SETTINGS);
  }
});

const previewFrameStyle = computed(() => ({
  width: PRESCRIPTION_PRINT_PAGE_DIMENSIONS.width,
  minHeight: PRESCRIPTION_PRINT_PAGE_DIMENSIONS.minHeight,
}));

function closeModal() {
  emit('update:modelValue', false);
}

function resolveRedirectTarget() {
  if (props.redirectTo) {
    return props.redirectTo;
  }

  return { name: 'queue.doctor' };
}

function redirectAfterModal() {
  if (!props.redirectAfterClose || hasRedirected.value) {
    return;
  }

  hasRedirected.value = true;
  router.push(resolveRedirectTarget());
}

function handleClose() {
  closeModal();
  emit('closed');
  redirectAfterModal();
}

async function handlePrint() {
  if (!props.printData) return;

  printing.value = true;
  try {
    await nextTick();
    await printPrescriptionElement(props.printAreaId, {
      pageSize: PRESCRIPTION_PRINT_SETTINGS.pageSize,
      orientation: PRESCRIPTION_PRINT_SETTINGS.orientation,
      margin: PRESCRIPTION_PRINT_SETTINGS.margin,
      fontSize: `${PRESCRIPTION_PRINT_SETTINGS.fontSize}pt`,
      letterheadHeight: PRESCRIPTION_PRINT_SETTINGS.letterheadHeight,
    }, {
      onAfterPrint: () => {
        closeModal();
        emit('printed');
        redirectAfterModal();
      },
    });
  } catch (error) {
    toastStore.error(error.message || 'Unable to print prescription.');
  } finally {
    printing.value = false;
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
