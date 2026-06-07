<template>
  <div class="max-w-7xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Prescription Print Settings</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Configure paper size, margins, letterhead space, and section font sizes. The sample preview updates live as you change settings.
      </p>
    </div>

    <div v-if="loading" class="h-64 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-700" />

    <div v-else class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-800">
      <PrescriptionPrintSettingsEditor :settings="settings" @saved="onSaved" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { prescriptionPrintSettingsService } from '@/services/prescriptionPrintSettingsService';
import { mergePrescriptionPrintSettings } from '@/utils/prescriptionPrintSettings';
import PrescriptionPrintSettingsEditor from '@/components/prescription/PrescriptionPrintSettingsEditor.vue';

const toastStore = useToastStore();
const loading = ref(true);
const settings = ref(null);

function onSaved(saved) {
  settings.value = saved;
}

onMounted(async () => {
  try {
    const { data } = await prescriptionPrintSettingsService.getSettings();
    settings.value = mergePrescriptionPrintSettings(data.data ?? data);
  } catch {
    toastStore.error('Failed to load prescription print settings.');
  } finally {
    loading.value = false;
  }
});
</script>
