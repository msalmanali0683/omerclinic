<template>

  <div class="max-w-6xl">

    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">New Clinical Scan</h2>



    <div class="space-y-6">

      <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">

        <div>

          <h3 class="font-semibold text-gray-900 dark:text-white">1. Find Patient Visit</h3>

          <p class="text-sm text-gray-500 dark:text-gray-400">Search doctor queue (all dates) by MR number, name, cell, or CNIC.</p>

        </div>



        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <BaseInput
            v-model="filters.search"
            placeholder="MR, name, cell, CNIC..."
            class="sm:col-span-2"
            @keyup.enter="flushSearch"
          />
          <BaseSelect
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            @change="searchPatients"
          />
          <BaseInput
            v-model="filters.visit_date"
            type="date"
            label="Visit date"
            @change="searchPatients"
          />
        </div>

        <div class="flex flex-wrap gap-3">
          <BaseButton variant="secondary" :loading="searchLoading" @click="searchPatients">Search</BaseButton>
          <BaseButton variant="ghost" @click="resetFilters">Reset</BaseButton>
        </div>



        <div v-if="searchLoading" class="h-24 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />



        <div v-else-if="searchResults.length" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">

          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

            <thead class="bg-gray-50 dark:bg-gray-900/50">

              <tr>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">MR#</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Patient</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Visit</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Doctor</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Status</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Scan</th>

                <th class="px-3 py-2 text-left font-semibold text-gray-500">Select</th>

              </tr>

            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

              <tr

                v-for="item in searchResults"

                :key="item.visit.id"

                :class="selectedVisit?.id === item.visit.id ? 'bg-teal-50 dark:bg-teal-900/20' : ''"

              >

                <td class="px-3 py-2 font-mono text-teal-600">{{ item.patient?.mr_number }}</td>

                <td class="px-3 py-2">

                  <div class="font-medium">{{ item.patient?.patient_name }}</div>

                  <div class="text-xs text-gray-500">

                    {{ formatGender(item.patient?.patient_gender) }} · {{ displayPatientAge(item.patient) }}

                  </div>

                </td>

                <td class="px-3 py-2">{{ item.visit?.visit_date }} {{ item.visit?.visit_time || '' }}</td>

                <td class="px-3 py-2">{{ item.visit?.doctor?.name || 'Unassigned' }}</td>

                <td class="px-3 py-2 capitalize">{{ (item.visit?.status || '').replace(/_/g, ' ') }}</td>

                <td class="px-3 py-2">

                  <span

                    v-if="item.has_completed_scan_on_visit"

                    class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300"

                  >

                    Completed

                  </span>

                  <span v-else class="text-xs text-gray-400">—</span>

                </td>

                <td class="px-3 py-2">

                  <BaseButton

                    variant="ghost"

                    size="sm"

                    @click="selectVisit(item)"

                  >

                    {{ selectedVisit?.id === item.visit.id ? 'Selected' : 'Select' }}

                  </BaseButton>

                </td>

              </tr>

            </tbody>

          </table>

        </div>



        <p v-else-if="searchedOnce" class="text-sm text-gray-500">No pending scan patients found.</p>

        <p v-if="errors.patient_id || errors.patient_visit_id" class="text-sm text-red-600">

          {{ errors.patient_id || errors.patient_visit_id }}

        </p>

      </section>



      <section

        v-if="selectedPatient"

        class="bg-gradient-to-r from-teal-50 to-white dark:from-teal-900/20 dark:to-gray-800 rounded-xl border border-teal-100 dark:border-teal-900/40 p-5 shadow-sm"

      >

        <h3 class="font-semibold mb-2 text-gray-900 dark:text-white">Selected Patient</h3>

        <p class="font-mono text-teal-600">{{ selectedPatient.mr_number }}</p>

        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedPatient.patient_name }}</p>

        <p class="text-sm text-gray-500">

          {{ selectedPatient.patient_father_name || '—' }} ·

          {{ formatGender(selectedPatient.patient_gender) }} ·

          {{ displayPatientAge(selectedPatient) }} ·

          {{ selectedPatient.patient_cell || '—' }}

        </p>

      </section>



      <section

        v-if="selectedVisit"

        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-5"

      >

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

          <div>

            <h3 class="font-semibold text-gray-900 dark:text-white">2. Scan Details</h3>

            <p class="text-sm text-gray-500 dark:text-gray-400">Choose a template and complete the scan form.</p>

          </div>

          <span class="inline-flex items-center self-start rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-800 dark:bg-teal-900/30 dark:text-teal-300">

            Visit #{{ selectedVisit.id }}

          </span>

        </div>



        <BaseSelect

          v-model="form.clinical_scan_template_id"

          label="Scan Template"

          :options="templateOptions"

          placeholder="Select template"

          :error="errors.clinical_scan_template_id"

          required

          @update:model-value="onTemplateChange"

        />

        <BaseInput
          v-if="form.clinical_scan_template_id"
          v-model="form.scan_name"
          label="Scan Name (print heading)"
          placeholder="Example: Abdominal Ultrasound"
          :error="errors.scan_name"
        />

        <div v-if="templateLoading" class="h-32 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />



        <template v-else-if="scanValues.length">

          <div class="rounded-xl border border-teal-100 dark:border-teal-900/30 bg-teal-50/40 dark:bg-teal-900/10 p-4">

            <h4 class="text-sm font-semibold text-teal-800 dark:text-teal-300 mb-3">Scan Findings</h4>

            <ClinicalScanDynamicFields v-model="scanValues" :error="errors.values" />

          </div>

          <div class="flex flex-wrap gap-3 pt-1">

            <BaseButton :loading="saving && savingStatus === 'completed'" @click="submit('completed')">

              Save Scan

            </BaseButton>

            <BaseButton variant="secondary" :loading="saving && savingStatus === 'draft'" @click="submit('draft')">

              Save as Draft

            </BaseButton>

            <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>

          </div>

        </template>

      </section>

    </div>



    <div

      v-if="showRescanConfirm"

      class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4"

      @click.self="cancelRescanConfirm"

    >

      <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl p-5">

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Scan already completed</h3>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">

          <strong>{{ pendingVisitItem?.patient?.patient_name }}</strong>

          already has a completed scan on this visit.

          Do you want to start a new scan for this patient?

        </p>

        <div class="mt-5 flex justify-end gap-3">

          <BaseButton variant="secondary" @click="cancelRescanConfirm">No</BaseButton>

          <BaseButton @click="confirmRescanSelection">Yes, new scan</BaseButton>

        </div>

      </div>

    </div>

  </div>

</template>



<script setup>

import { onMounted, reactive, ref } from 'vue';

import { useRouter } from 'vue-router';

import { useToastStore } from '@/stores/toast';

import { clinicalScanService } from '@/services/clinicalScanService';

import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';

import { useAutoSearch } from '@/composables/useAutoSearch';
import { useFormErrors } from '@/composables/useFormErrors';

import { buildScanValuesFromTemplate, serializeScanValues } from '@/utils/clinicalScans';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';

import BaseInput from '@/components/ui/BaseInput.vue';

import BaseSelect from '@/components/ui/BaseSelect.vue';

import ClinicalScanDynamicFields from '@/components/clinical-scans/ClinicalScanDynamicFields.vue';



const router = useRouter();

const toastStore = useToastStore();

const { errors, setErrors, clearErrors } = useFormErrors();



const filters = reactive({
  search: '',
  status: 'pending_prescription,in_consultation',
  visit_date: '',
});
const { flush: flushSearch } = useAutoSearch(() => filters.search, searchPatients, { minLength: 1 });

const statusOptions = [
  { value: 'pending_prescription,in_consultation', label: 'All Active' },
  { value: 'pending_prescription', label: 'Pending Prescription' },
  { value: 'in_consultation', label: 'In Consultation' },
];

const searchResults = ref([]);

const searchLoading = ref(false);

const searchedOnce = ref(false);

const selectedPatient = ref(null);

const selectedVisit = ref(null);



const showRescanConfirm = ref(false);

const pendingVisitItem = ref(null);



const templateOptions = ref([]);

const templateLoading = ref(false);

const scanValues = ref([]);



const form = reactive({

  clinical_scan_template_id: '',

  scan_name: '',

});



const saving = ref(false);

const savingStatus = ref('');



function resetFilters() {
  filters.search = '';
  filters.status = 'pending_prescription,in_consultation';
  filters.visit_date = '';
  searchPatients();
}

async function searchPatients() {
  searchLoading.value = true;

  searchedOnce.value = true;

  try {

    const { data } = await clinicalScanService.searchQueuePatients({
      search: filters.search || undefined,
      status: filters.status || undefined,
      date: filters.visit_date || undefined,
      today_only: false,
      limit: 100,
    });

    searchResults.value = data.data ?? [];

  } catch (e) {

    toastStore.error(e.response?.data?.message ?? 'Patient search failed.');

  } finally {

    searchLoading.value = false;

  }

}



function applyVisitSelection(item) {

  selectedPatient.value = item.patient;

  selectedVisit.value = item.visit;

  form.clinical_scan_template_id = '';

  form.scan_name = '';

  scanValues.value = [];

}



function selectVisit(item) {

  if (selectedVisit.value?.id === item.visit?.id) {

    return;

  }



  if (item.has_completed_scan_on_visit) {

    pendingVisitItem.value = item;

    showRescanConfirm.value = true;

    return;

  }



  applyVisitSelection(item);

}



function confirmRescanSelection() {

  if (pendingVisitItem.value) {

    applyVisitSelection(pendingVisitItem.value);

  }

  cancelRescanConfirm();

}



function cancelRescanConfirm() {

  showRescanConfirm.value = false;

  pendingVisitItem.value = null;

}



async function loadTemplateOptions() {

  try {

    const { data } = await clinicalScanTemplateService.getTemplateOptions();

    templateOptions.value = data.data ?? [];

  } catch (e) {

    toastStore.error(e.response?.data?.message ?? 'Failed to load templates.');

  }

}



async function onTemplateChange(templateId) {

  if (!templateId) {

    scanValues.value = [];

    return;

  }



  templateLoading.value = true;

  try {

    const { data } = await clinicalScanTemplateService.getTemplate(templateId);

    const template = data.data ?? data;

    form.scan_name = template.template_name ?? '';

    scanValues.value = buildScanValuesFromTemplate(template.fields ?? []);

  } catch (e) {

    toastStore.error(e.response?.data?.message ?? 'Failed to load template fields.');

    scanValues.value = [];

  } finally {

    templateLoading.value = false;

  }

}



async function submit(status) {

  if (!selectedPatient.value || !selectedVisit.value) {

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

      patient_id: selectedPatient.value.id,

      patient_visit_id: selectedVisit.value.id,

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

    router.push(`/clinical-scans/${scan.id}`);

  } catch (e) {

    setErrors(e);

    toastStore.error(e.response?.data?.message ?? 'Save failed.');

  } finally {

    saving.value = false;

    savingStatus.value = '';

  }

}



onMounted(() => {

  loadTemplateOptions();

  searchPatients();

});

</script>


