<template>
  <div class="grid gap-6" :class="showSamplePreview ? 'xl:grid-cols-2 xl:items-start' : ''">
    <form class="space-y-6" @submit.prevent="save">
    <section class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
      <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Paper Size</h4>
      <BaseSelect
        v-model="form.active_paper_key"
        label="Active paper for printing"
        :options="paperOptions"
      />
      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Margins below apply to the selected paper. Both A4 and Legal presets are saved.
      </p>
    </section>

    <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
      <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
        Margins — {{ activePaperLabel }}
      </h4>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <BaseInput v-model="activePaperPreset.margin_top" label="Top" placeholder="0.1in" />
        <BaseInput v-model="activePaperPreset.margin_right" label="Right" placeholder="0.32in" />
        <BaseInput v-model="activePaperPreset.margin_bottom" label="Bottom" placeholder="0.2in" />
        <BaseInput v-model="activePaperPreset.margin_left" label="Left" placeholder="0.5in" />
      </div>
    </section>

    <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
      <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Letterhead</h4>
      <BaseInput
        v-model="form.letterhead_height"
        label="Letterhead space height"
        placeholder="2.45in"
        hint="Empty area at top for pre-printed letterhead"
      />
    </section>

    <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
      <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Font Sizes (pt)</h4>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <BaseInput v-model="form.font_size_base" label="Base / body" type="number" min="8" max="24" />
        <BaseInput v-model="form.font_size_vitals" label="Vitals" type="number" min="8" max="24" />
        <BaseInput v-model="form.font_size_clinical_scans" label="Clinical scans" type="number" min="8" max="24" />
        <BaseInput v-model="form.font_size_medicines" label="Medicines" type="number" min="8" max="24" />
        <BaseInput v-model="form.font_size_medicine_dose" label="Medicine dose" type="number" min="8" max="24" />
      </div>
    </section>

    <div class="flex flex-wrap gap-3">
      <BaseButton type="submit" :loading="saving">Save Settings</BaseButton>
      <BaseButton v-if="showReset" type="button" variant="secondary" @click="resetDefaults">Reset Defaults</BaseButton>
    </div>
    </form>

    <div v-if="showSamplePreview" class="xl:sticky xl:top-4">
      <PrescriptionPrintSamplePreview
        :print-settings="previewSettings"
        :print-area-id="samplePreviewAreaId"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useToastStore } from '@/stores/toast';
import { prescriptionPrintSettingsService } from '@/services/prescriptionPrintSettingsService';
import {
  DEFAULT_PAPER_PRESETS,
  PAPER_SIZE_OPTIONS,
  buildSettingsPayload,
  getDefaultResolvedSettings,
  mergePrescriptionPrintSettings,
} from '@/utils/prescriptionPrintSettings';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import PrescriptionPrintSamplePreview from '@/components/prescription/PrescriptionPrintSamplePreview.vue';

const props = defineProps({
  settings: { type: Object, default: null },
  showReset: { type: Boolean, default: true },
  showSamplePreview: { type: Boolean, default: true },
  samplePreviewAreaId: { type: String, default: 'prescription-print-settings-sample' },
});

const emit = defineEmits(['saved', 'change']);

const toastStore = useToastStore();
const saving = ref(false);
const paperOptions = PAPER_SIZE_OPTIONS;

const form = reactive(createFormState(props.settings));

function createFormState(settings) {
  const resolved = mergePrescriptionPrintSettings(settings);

  return {
    active_paper_key: resolved.active_paper_key,
    letterhead_height: resolved.letterhead_height,
    font_size_base: resolved.font_size_base,
    font_size_vitals: resolved.font_size_vitals,
    font_size_clinical_scans: resolved.font_size_clinical_scans,
    font_size_medicines: resolved.font_size_medicines,
    font_size_medicine_dose: resolved.font_size_medicine_dose,
    paper_presets: structuredClone(resolved.paper_presets),
  };
}

const activePaperPreset = computed({
  get: () => form.paper_presets[form.active_paper_key] ?? form.paper_presets.A4,
  set: (value) => {
    form.paper_presets[form.active_paper_key] = value;
  },
});

const activePaperLabel = computed(() => activePaperPreset.value?.label ?? form.active_paper_key);

const previewSettings = computed(() => mergePrescriptionPrintSettings(buildSettingsPayload(form)));

watch(
  () => props.settings,
  (value) => {
    Object.assign(form, createFormState(value));
  },
  { deep: true },
);

watch(
  form,
  () => {
    emit('change', mergePrescriptionPrintSettings(buildSettingsPayload(form)));
  },
  { deep: true },
);

function resetDefaults() {
  Object.assign(form, createFormState(getDefaultResolvedSettings()));
  emit('change', mergePrescriptionPrintSettings(buildSettingsPayload(form)));
}

async function save() {
  saving.value = true;
  try {
    const { data } = await prescriptionPrintSettingsService.updateSettings(buildSettingsPayload(form));
    const saved = mergePrescriptionPrintSettings(data.data ?? data);
    Object.assign(form, createFormState(saved));
    toastStore.success(data.message ?? 'Prescription print settings saved.');
    emit('saved', saved);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to save print settings.');
  } finally {
    saving.value = false;
  }
}

defineExpose({ save, resetDefaults });
</script>
