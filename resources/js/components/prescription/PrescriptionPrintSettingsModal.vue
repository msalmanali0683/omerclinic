<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-2 sm:p-4 no-print">
        <div class="fixed inset-0 bg-black/50" @click="handleClose" />
        <div
          class="relative flex max-h-[95vh] w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
          :class="activeTab === 'settings' ? 'max-w-6xl' : 'max-w-3xl'"
        >
          <div class="no-print flex items-center justify-between border-b border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700">
            <div class="flex min-w-0 flex-1 items-center gap-4">
              <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
              <div v-if="canManageSettings" class="flex rounded-lg border border-gray-200 p-0.5 dark:border-gray-700">
                <button
                  type="button"
                  class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                  :class="activeTab === 'preview' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                  @click="activeTab = 'preview'"
                >
                  Preview
                </button>
                <button
                  type="button"
                  class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                  :class="activeTab === 'settings' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                  @click="activeTab = 'settings'"
                >
                  Print Settings
                </button>
              </div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="handleClose">✕</button>
          </div>

          <div v-if="activeTab === 'preview'" class="print-preview-panel flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="preview-shell overflow-auto rounded-lg border border-gray-300 bg-gray-100 p-3 sm:p-4 dark:border-gray-600 dark:bg-gray-950">
              <div class="mx-auto bg-white shadow-sm" :style="previewFrameStyle">
                <VisitPrintPreview
                  v-if="printData"
                  :print-data="printData"
                  :print-settings="resolvedSettings"
                  :show-empty-clinical-scans-as-na="showEmptyClinicalScansAsNa"
                  :print-area-id="printAreaId"
                />
              </div>
            </div>
          </div>

          <div v-else class="flex-1 overflow-y-auto p-4 sm:p-6">
            <PrescriptionPrintSettingsEditor
              ref="editorRef"
              :settings="resolvedSettings"
              :show-reset="true"
              sample-preview-area-id="prescription-print-modal-sample"
              @change="onSettingsChange"
              @saved="onSettingsSaved"
            />
          </div>

          <div class="modal-footer flex justify-end gap-3 border-t border-gray-200 px-4 py-4 sm:px-6 dark:border-gray-700 no-print">
            <BaseButton type="button" variant="secondary" @click="handleClose">Close</BaseButton>
            <BaseButton
              v-if="activeTab === 'settings' && canManageSettings"
              type="button"
              :loading="savingSettings"
              @click="saveSettings"
            >
              Save Settings
            </BaseButton>
            <BaseButton v-else type="button" :loading="printing" @click="handlePrint">Print</BaseButton>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { prescriptionPrintSettingsService } from '@/services/prescriptionPrintSettingsService';
import {
  applyPrescriptionPrintPageStyle,
  getDefaultResolvedSettings,
  getPreviewFrameStyle,
  getPrintElementOptions,
  mergePrescriptionPrintSettings,
} from '@/utils/prescriptionPrintSettings';
import { printPrescriptionElement } from '@/utils/printPrescription';
import VisitPrintPreview from '@/components/prints/VisitPrintPreview.vue';
import PrescriptionPrintSettingsEditor from '@/components/prescription/PrescriptionPrintSettingsEditor.vue';
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

const emit = defineEmits(['update:modelValue', 'printed', 'closed', 'settings-saved']);

const authStore = useAuthStore();
const toastStore = useToastStore();
const router = useRouter();
const printing = ref(false);
const savingSettings = ref(false);
const hasRedirected = ref(false);
const activeTab = ref('preview');
const editorRef = ref(null);
const printSettings = ref(getDefaultResolvedSettings());

const canManageSettings = computed(() => authStore.can('manage prescription print settings'));

const resolvedSettings = computed(() => mergePrescriptionPrintSettings(printSettings.value));

const previewFrameStyle = computed(() => getPreviewFrameStyle(resolvedSettings.value));

watch(() => props.modelValue, async (open) => {
  if (!open) {
    return;
  }

  hasRedirected.value = false;
  activeTab.value = 'preview';
  printSettings.value = mergePrescriptionPrintSettings(
    props.printData?.print_settings ?? getDefaultResolvedSettings(),
  );
  applyPrescriptionPrintPageStyle(resolvedSettings.value);

  if (canManageSettings.value) {
    try {
      const { data } = await prescriptionPrintSettingsService.getSettings();
      printSettings.value = mergePrescriptionPrintSettings(data.data ?? data);
      applyPrescriptionPrintPageStyle(resolvedSettings.value);
    } catch {
      // Keep print payload settings when API load fails.
    }
  }
});

watch(resolvedSettings, (settings) => {
  if (props.modelValue) {
    applyPrescriptionPrintPageStyle(settings);
  }
}, { deep: true });

function onSettingsChange(settings) {
  printSettings.value = settings;
}

function onSettingsSaved(settings) {
  printSettings.value = settings;
  emit('settings-saved', settings);
}

async function saveSettings() {
  if (!editorRef.value?.save) {
    return;
  }

  savingSettings.value = true;
  try {
    await editorRef.value.save();
    activeTab.value = 'preview';
  } finally {
    savingSettings.value = false;
  }
}

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
    await printPrescriptionElement(props.printAreaId, getPrintElementOptions(resolvedSettings.value), {
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
