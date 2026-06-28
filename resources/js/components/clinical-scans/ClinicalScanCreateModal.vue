<template>
  <BaseModal
    :model-value="modelValue"
    size="full"
    @update:model-value="handleClose"
  >
    <template #title>
      <div class="space-y-1 pr-2">
        <p class="text-xs font-medium uppercase tracking-wide text-teal-600 dark:text-teal-400">
          Step {{ step === 'template' ? '1' : '2' }} of 2 · {{ step === 'template' ? 'Select scan type' : 'Complete scan' }}
        </p>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ step === 'template' ? 'Choose Scan Template' : selectedTemplateName || 'Scan Form' }}
        </h3>
        <p v-if="patient" class="text-sm text-gray-500 dark:text-gray-400 truncate">
          {{ patient.mr_number }} · {{ patient.patient_name }}
          <span v-if="visit"> · Visit #{{ visit.id }}</span>
        </p>
      </div>
    </template>

    <div v-if="step === 'template'" class="space-y-4">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Select the scan type for this patient. The form will open with the matching template fields.
      </p>

      <div v-if="!templateOptions.length" class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-600">
        No active scan templates found. Ask an administrator to create templates first.
      </div>

      <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <button
          v-for="option in templateOptions"
          :key="option.id"
          type="button"
          class="rounded-2xl border p-4 text-left transition-colors"
          :class="option.id === form.clinical_scan_template_id
            ? 'border-teal-500 bg-teal-50 shadow-sm dark:border-teal-500 dark:bg-teal-900/20'
            : 'border-gray-200 bg-white hover:border-teal-300 hover:bg-teal-50/60 dark:border-gray-700 dark:bg-gray-900/40 dark:hover:border-teal-700'"
          :disabled="templateLoading"
          @click="selectTemplate(option.id)"
        >
          <span class="block text-base font-semibold text-gray-900 dark:text-white">{{ option.label }}</span>
          <span v-if="option.description" class="mt-1 block text-sm text-gray-500 dark:text-gray-400 line-clamp-3">
            {{ option.description }}
          </span>
        </button>
      </div>

      <p v-if="errors.clinical_scan_template_id" class="text-sm text-red-600">{{ errors.clinical_scan_template_id }}</p>
    </div>

    <div v-else class="space-y-5 pb-2">
      <div class="rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-3 dark:border-teal-900/40 dark:bg-teal-900/15">
        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedTemplateName }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Fill in the findings below and save when complete.</p>
      </div>

      <BaseInput
        v-model="form.scan_name"
        label="Scan Name (print heading)"
        placeholder="Example: Abdominal Ultrasound"
        :error="errors.scan_name"
      />

      <div v-if="templateLoading" class="h-32 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />

      <div v-else-if="scanValues.length" :class="clinicalScanFindingsPanelClass">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between mb-4">
          <h4 :class="clinicalScanFindingsPanelTitleClass" class="!mb-0">Scan Findings</h4>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ scanValues.length }} fields · use presets or type findings
          </p>
        </div>
        <ClinicalScanDynamicFields v-model="scanValues" :error="errors.values" />
      </div>
    </div>

    <template #footer>
      <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
          <BaseButton variant="secondary" @click="handleClose(false)">Cancel</BaseButton>
          <BaseButton
            v-if="step === 'form'"
            variant="ghost"
            @click="goBackToTemplateStep"
          >
            Back to scan types
          </BaseButton>
        </div>

        <div v-if="step === 'form'" class="flex flex-wrap gap-2 sm:justify-end">
          <BaseButton
            variant="secondary"
            :loading="saving && savingStatus === 'draft'"
            @click="submit('draft')"
          >
            Save as Draft
          </BaseButton>
          <BaseButton
            :loading="saving && savingStatus === 'completed'"
            @click="submit('completed')"
          >
            Save Scan
          </BaseButton>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { useFormErrors } from '@/composables/useFormErrors';
import { clinicalScanService } from '@/services/clinicalScanService';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { buildScanValuesFromTemplate, serializeScanValues } from '@/utils/clinicalScans';
import { clinicalScanFindingsPanelClass, clinicalScanFindingsPanelTitleClass } from '@/utils/clinicalScanFieldTheme';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import ClinicalScanDynamicFields from '@/components/clinical-scans/ClinicalScanDynamicFields.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  patient: { type: Object, default: null },
  visit: { type: Object, default: null },
  templateOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const step = ref('template');
const templateLoading = ref(false);
const selectedTemplateName = ref('');
const scanValues = ref([]);
const saving = ref(false);
const savingStatus = ref('');

const form = reactive({
  clinical_scan_template_id: '',
  scan_name: '',
});

function resetFormState() {
  step.value = 'template';
  form.clinical_scan_template_id = '';
  form.scan_name = '';
  selectedTemplateName.value = '';
  scanValues.value = [];
  templateLoading.value = false;
  saving.value = false;
  savingStatus.value = '';
  clearErrors();
}

function handleClose(value) {
  if (!value) {
    resetFormState();
    emit('update:modelValue', false);
  }
}

function goBackToTemplateStep() {
  step.value = 'template';
  form.clinical_scan_template_id = '';
  form.scan_name = '';
  selectedTemplateName.value = '';
  scanValues.value = [];
  clearErrors();
}

async function selectTemplate(templateId) {
  if (!templateId) {
    return;
  }

  form.clinical_scan_template_id = String(templateId);
  templateLoading.value = true;
  clearErrors();

  try {
    const { data } = await clinicalScanTemplateService.getTemplate(templateId);
    const template = data.data ?? data;

    selectedTemplateName.value = template.template_name ?? '';
    form.scan_name = template.template_name ?? '';
    scanValues.value = buildScanValuesFromTemplate(template.fields ?? []);
    step.value = 'form';
  } catch (error) {
    toastStore.error(error.response?.data?.message ?? 'Failed to load template fields.');
    form.clinical_scan_template_id = '';
    scanValues.value = [];
  } finally {
    templateLoading.value = false;
  }
}

async function submit(status) {
  if (!props.patient?.id || !props.visit?.id) {
    toastStore.error('Please select a patient visit.');
    return;
  }

  if (!form.clinical_scan_template_id) {
    toastStore.error('Please select a scan template.');
    return;
  }

  clearErrors();
  saving.value = true;
  savingStatus.value = status;

  try {
    const payload = {
      patient_id: props.patient.id,
      patient_visit_id: props.visit.id,
      clinical_scan_template_id: Number(form.clinical_scan_template_id),
      scan_name: form.scan_name?.trim() || null,
      status,
      notes: null,
      impression: null,
      values: serializeScanValues(scanValues.value),
    };

    const { data } = await clinicalScanService.createScan(payload);
    const scan = data.data ?? data;

    toastStore.success(status === 'draft' ? 'Scan saved as draft.' : 'Clinical scan saved.');
    emit('saved', scan);
    handleClose(false);
    router.push(`/clinical-scans/${scan.id}`);
  } catch (error) {
    setErrors(error);
    toastStore.error(error.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
    savingStatus.value = '';
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      resetFormState();
    }
  },
);
</script>
