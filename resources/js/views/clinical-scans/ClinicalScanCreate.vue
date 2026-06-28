<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">New Clinical Scan</h2>

    <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
      <div>
        <h3 class="font-semibold text-gray-900 dark:text-white">Find Patient Visit</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Search doctor queue by MR number, name, cell, or CNIC. Click Select to choose scan type and complete the scan.
        </p>
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
              :class="activeVisitId === item.visit.id ? 'bg-teal-50 dark:bg-teal-900/20' : ''"
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
                  {{ activeVisitId === item.visit.id ? 'Open' : 'Select' }}
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else-if="searchedOnce" class="text-sm text-gray-500">No pending scan patients found.</p>
    </section>

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

    <ClinicalScanCreateModal
      v-model="showScanModal"
      :patient="selectedPatient"
      :visit="selectedVisit"
      :template-options="templateOptions"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { clinicalScanService } from '@/services/clinicalScanService';
import { clinicalScanTemplateService } from '@/services/clinicalScanTemplateService';
import { useAutoSearch } from '@/composables/useAutoSearch';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import ClinicalScanCreateModal from '@/components/clinical-scans/ClinicalScanCreateModal.vue';

const toastStore = useToastStore();

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
const showScanModal = ref(false);
const showRescanConfirm = ref(false);
const pendingVisitItem = ref(null);
const templateOptions = ref([]);

const activeVisitId = computed(() => selectedVisit.value?.id ?? null);

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

function openScanModal(item) {
  selectedPatient.value = item.patient;
  selectedVisit.value = item.visit;
  showScanModal.value = true;
}

function selectVisit(item) {
  if (item.has_completed_scan_on_visit) {
    pendingVisitItem.value = item;
    showRescanConfirm.value = true;
    return;
  }

  openScanModal(item);
}

function confirmRescanSelection() {
  if (pendingVisitItem.value) {
    openScanModal(pendingVisitItem.value);
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

onMounted(() => {
  loadTemplateOptions();
  searchPatients();
});
</script>
