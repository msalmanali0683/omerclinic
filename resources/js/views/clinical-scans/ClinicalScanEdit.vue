<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit Clinical Scan</h2>

    <div v-if="pageLoading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <template v-else-if="scan">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm mb-6">
        <h3 class="font-semibold mb-2">Patient &amp; Visit</h3>
        <p class="font-mono text-teal-600">{{ scan.patient?.mr_number }}</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ scan.patient?.patient_name }}</p>
        <p class="text-sm text-gray-500">
          {{ scan.patient?.patient_father_name || '—' }} ·
          {{ formatGender(scan.patient?.patient_gender) }} ·
          {{ displayPatientAge(scan.patient) }}
        </p>
        <p class="text-sm text-gray-500 mt-2">
          Visit: {{ scan.visit?.visit_date || '—' }} · Doctor: {{ scan.visit?.doctor?.name || 'Unassigned' }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
          Template: {{ scan.scan_template_name || scan.template?.template_name || '—' }}
        </p>
      </div>

      <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-5" @submit.prevent="submit('completed')">
        <BaseInput
          v-model="form.scan_name"
          label="Scan Name (print heading)"
          placeholder="Example: Abdominal Ultrasound"
          :error="errors.scan_name"
        />

        <div :class="clinicalScanFindingsPanelClass">
          <h4 :class="clinicalScanFindingsPanelTitleClass">Scan Findings</h4>
          <ClinicalScanDynamicFields v-model="scanValues" :error="errors.values" />
        </div>

        <div class="flex flex-wrap gap-3">
          <BaseButton type="submit" :loading="saving && savingStatus === 'completed'">Save Scan</BaseButton>
          <BaseButton
            type="button"
            variant="secondary"
            :loading="saving && savingStatus === 'draft'"
            @click="submit('draft')"
          >
            Save as Draft
          </BaseButton>
          <BaseButton type="button" variant="secondary" @click="$router.push(`/clinical-scans/${scan.id}`)">Cancel</BaseButton>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { clinicalScanService } from '@/services/clinicalScanService';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { useFormErrors } from '@/composables/useFormErrors';
import { applyLegacyScanMetaToValues, buildScanValuesForEdit, extractResourceList, serializeScanValues } from '@/utils/clinicalScans';
import { clinicalScanFindingsPanelClass, clinicalScanFindingsPanelTitleClass } from '@/utils/clinicalScanFieldTheme';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import ClinicalScanDynamicFields from '@/components/clinical-scans/ClinicalScanDynamicFields.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const scan = ref(null);
const scanValues = ref([]);
const pageLoading = ref(true);
const saving = ref(false);
const savingStatus = ref('');

const form = reactive({
  scan_name: '',
});

async function submit(status) {
  clearErrors();
  saving.value = true;
  savingStatus.value = status;

  try {
    const payload = {
      scan_name: form.scan_name?.trim() || null,
      status,
      notes: null,
      impression: null,
      values: serializeScanValues(scanValues.value),
    };

    await clinicalScanService.updateScan(route.params.id, payload);
    toastStore.success(status === 'draft' ? 'Scan saved as draft.' : 'Clinical scan updated.');
    router.push(`/clinical-scans/${route.params.id}`);
  } catch (e) {
    setErrors(e);
    const status = e.response?.status;
    const message = status === 403
      ? (e.response?.data?.message ?? 'You are not authorized to update this clinical scan.')
      : (e.response?.data?.message ?? 'Update failed.');
    toastStore.error(message);
  } finally {
    saving.value = false;
    savingStatus.value = '';
  }
}

onMounted(async () => {
  try {
    const { data } = await clinicalScanService.getScan(route.params.id);
    const row = data.data ?? data;
    scan.value = row;
    form.scan_name = row.scan_name || row.scan_template_name || '';

    let templateFields = extractResourceList(row.template?.fields);

    if (!templateFields.length && row.clinical_scan_template_id) {
      const templateRes = await clinicalScanTemplateService.getTemplate(row.clinical_scan_template_id);
      const template = templateRes.data.data ?? templateRes.data;
      templateFields = extractResourceList(template.fields);
    }

    scanValues.value = applyLegacyScanMetaToValues(
      buildScanValuesForEdit(templateFields, extractResourceList(row.values)),
      { notes: row.notes, impression: row.impression },
    );
  } catch {
    toastStore.error('Failed to load scan.');
    router.push('/clinical-scans');
  } finally {
    pageLoading.value = false;
  }
});
</script>
