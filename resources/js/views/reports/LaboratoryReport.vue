<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Laboratory Report</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Patient lab tests with prices and totals</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton v-if="canPrint" variant="secondary" :loading="printing" @click="openPrint">Print</BaseButton>
        <BaseButton v-if="canExportPdf" :loading="exporting" @click="exportPdf">Export PDF</BaseButton>
      </div>
    </div>

    <LaboratoryReportFilters v-model="filters" :doctor-options="doctorOptions" class="mb-4" />

    <div class="flex flex-wrap gap-2 mb-4">
      <BaseButton @click="search">Search</BaseButton>
      <BaseButton variant="secondary" @click="resetFilters">Reset</BaseButton>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Total Tests</div>
        <div class="text-xl font-semibold">{{ summary.total_results ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Total Patients</div>
        <div class="text-xl font-semibold">{{ summary.total_patients ?? 0 }}</div>
      </div>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
        <div class="text-xs text-gray-500">Grand Total</div>
        <div class="text-xl font-semibold">{{ formatCurrency(summary.grand_total_price) }}</div>
      </div>
    </div>

    <LaboratoryReportTable :rows="rows" :loading="loading" />

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

    <LaboratoryReportPrintModal v-model="showPrintModal" :print-data="printData" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { reportService } from '@/services/reportService';
import { userService } from '@/services/userService';
import { formatCurrency } from '@/utils/formatters';
import LaboratoryReportFilters from '@/components/reports/LaboratoryReportFilters.vue';
import LaboratoryReportTable from '@/components/reports/LaboratoryReportTable.vue';
import LaboratoryReportPrintModal from '@/components/reports/LaboratoryReportPrintModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const defaultFilters = () => ({
  from_date: '',
  to_date: '',
  mr_number: '',
  patient_name: '',
  patient_father_name: '',
  patient_gender: '',
  test_name: '',
  test_code: '',
  status: '',
  doctor_id: '',
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

const canPrint = computed(() => authStore.can('print laboratory reports'));
const canExportPdf = computed(() => authStore.can('export laboratory reports pdf'));

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
    const { data } = await reportService.getLaboratoryReport(buildParams(page));
    rows.value = data.data ?? [];
    summary.value = data.summary ?? {};
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
    pagination.per_page = data.meta?.per_page ?? 25;
    pagination.total = data.meta?.total ?? 0;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load laboratory report.');
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
    const { data } = await reportService.getLaboratoryReportPrintData(buildExportParams());
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
    const response = await reportService.exportLaboratoryReportPdf(buildExportParams());
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `laboratory-report-${new Date().toISOString().slice(0, 10)}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    toastStore.success('Laboratory report PDF downloaded.');
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
