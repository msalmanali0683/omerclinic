<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Patient Report</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Patient personal information with visit summary</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton v-if="canPrint" variant="secondary" :loading="printing" @click="openPrint">Print</BaseButton>
        <BaseButton v-if="canExportPdf" :loading="exporting" @click="exportPdf">Export PDF</BaseButton>
      </div>
    </div>

    <PatientReportFilters v-model="filters" :doctor-options="doctorOptions" class="mb-4" />

    <div class="flex flex-wrap gap-2 mb-4">
      <BaseButton @click="search">Search</BaseButton>
      <BaseButton variant="secondary" @click="resetFilters">Reset</BaseButton>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Total Patients</div>
        <div class="text-xl font-semibold">{{ summary.total_patients ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Total Visits</div>
        <div class="text-xl font-semibold">{{ summary.total_visits ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Male</div>
        <div class="text-xl font-semibold">{{ summary.male_count ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Female</div>
        <div class="text-xl font-semibold">{{ summary.female_count ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Other</div>
        <div class="text-xl font-semibold">{{ summary.other_count ?? 0 }}</div>
      </div>
    </div>

    <PatientReportTable
      :rows="rows"
      :loading="loading"
      @view-patient="viewPatient"
    />

    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4 text-sm">
      <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">
          Previous
        </BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">
          Next
        </BaseButton>
      </div>
    </div>

    <PatientReportPrintModal v-model="showPrintModal" :print-data="printData" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { reportService } from '@/services/reportService';
import { userService } from '@/services/userService';
import PatientReportFilters from '@/components/reports/PatientReportFilters.vue';
import PatientReportTable from '@/components/reports/PatientReportTable.vue';
import PatientReportPrintModal from '@/components/reports/PatientReportPrintModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const router = useRouter();

const defaultFilters = () => ({
  report_type: 'patient',
  filter_by: 'registration_date',
  from_date: '',
  to_date: '',
  mr_number: '',
  patient_name: '',
  patient_father_name: '',
  patient_gender: '',
  age_from: '',
  age_to: '',
  age_unit: '',
  patient_cell: '',
  patient_cnic: '',
  status: '',
  doctor_id: '',
  has_prescription: '',
  has_laboratory_result: '',
  has_clinical_scan: '',
  search: '',
});

const filters = reactive(defaultFilters());
const rows = ref([]);
const summary = ref({});
const loading = ref(false);
const printing = ref(false);
const exporting = ref(false);
const showPrintModal = ref(false);
const printData = ref(null);
const doctorOptions = ref([]);
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
});

const canPrint = computed(() => authStore.can('print patient reports'));
const canExportPdf = computed(() => authStore.can('export patient reports pdf'));

function buildParams(page = pagination.current_page) {
  const params = { page, per_page: pagination.per_page };

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params[key] = value;
    }
  });

  return params;
}

function buildExportParams() {
  const params = {};

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params[key] = value;
    }
  });

  return params;
}

async function fetch(page = 1) {
  loading.value = true;

  try {
    const { data } = await reportService.getPatientReport(buildParams(page));
    rows.value = data.data ?? [];
    summary.value = data.summary ?? {};
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
    pagination.per_page = data.meta?.per_page ?? 25;
    pagination.total = data.meta?.total ?? 0;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load patient report.');
    rows.value = [];
  } finally {
    loading.value = false;
  }
}

function search() {
  fetch(1);
}

function resetFilters() {
  Object.assign(filters, defaultFilters());
  fetch(1);
}

function goPage(page) {
  fetch(page);
}

async function openPrint() {
  printing.value = true;

  try {
    const { data } = await reportService.getPatientReportPrintData(buildExportParams());
    printData.value = data.print_data ?? null;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load report for printing.');
  } finally {
    printing.value = false;
  }
}

async function exportPdf() {
  exporting.value = true;

  try {
    const response = await reportService.exportPatientReportPdf(buildExportParams());
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `patient-report-${new Date().toISOString().slice(0, 10)}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    toastStore.success('Patient report PDF downloaded.');
  } catch (e) {
    if (e.response?.data instanceof Blob) {
      const text = await e.response.data.text();
      try {
        const json = JSON.parse(text);
        toastStore.error(json.message ?? 'PDF export failed.');
      } catch {
        toastStore.error('PDF export failed.');
      }
    } else {
      toastStore.error(e.response?.data?.message ?? 'PDF export failed.');
    }
  } finally {
    exporting.value = false;
  }
}

function viewPatient(patientId) {
  if (patientId) {
    router.push(`/patients/search?patient_id=${patientId}`);
  }
}

async function loadDoctors() {
  try {
    const { data } = await userService.listDoctors();
    doctorOptions.value = (data.data ?? data ?? []).map((doctor) => ({
      value: doctor.id,
      label: doctor.name,
    }));
  } catch {
    doctorOptions.value = [];
  }
}

onMounted(async () => {
  await loadDoctors();
  await fetch();
});
</script>
