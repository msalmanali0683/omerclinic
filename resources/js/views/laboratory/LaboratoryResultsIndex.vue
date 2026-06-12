<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Laboratory Results</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Patient-wise lab tests and results</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          v-if="authStore.can('create lab bills')"
          variant="secondary"
          @click="$router.push('/laboratory/billing')"
        >
          Test Billing
        </BaseButton>
      </div>
    </div>

    <div v-if="!selectedPatientId" class="space-y-4">
      <BaseInput v-model="search" placeholder="Search patients..." class="max-w-md" @keyup.enter="flushSearch" />
      <BaseButton variant="secondary" class="ml-0 sm:ml-2" @click="loadPatients">Search</BaseButton>

      <BaseTable :columns="patientColumns" :rows="patients" :loading="loadingPatients">
        <template #cell-actions="{ row }">
          <BaseButton size="sm" variant="ghost" @click="openPatient(row.patient_id)">View Tests</BaseButton>
        </template>
      </BaseTable>
    </div>

    <div v-else class="space-y-4">
      <BaseButton variant="ghost" size="sm" @click="backToPatients">← Back to Patients</BaseButton>

      <div v-if="visitsOverview" class="bg-white dark:bg-gray-800 rounded-xl border p-4 shadow-sm">
        <h3 class="font-semibold text-lg mb-1">{{ visitsOverview.patient?.patient_name }}</h3>
        <p class="text-sm text-gray-500 font-mono">{{ visitsOverview.patient?.patient_code }}</p>
      </div>

      <div v-if="loadingVisits" class="h-24 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />

      <template v-else-if="visitsOverview">
        <div v-if="visitsOverview.visits?.length" class="space-y-2">
          <h4 class="font-medium text-gray-700 dark:text-gray-300">Visits</h4>
          <div
            v-for="visit in visitsOverview.visits"
            :key="visit.id"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 flex justify-between items-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900/40"
            @click="loadVisitTests(visit.id)"
          >
            <div>
              <div class="font-medium">{{ visit.visit_no }} · {{ visit.visit_date }}</div>
              <div class="text-xs text-gray-500">{{ visit.doctor_name || 'No doctor' }}</div>
            </div>
            <div class="text-xs text-gray-500 text-right">
              {{ visit.tests_count }} tests · {{ visit.draft_tests_count }} draft
            </div>
          </div>
        </div>

        <div
          v-if="visitsOverview.no_visit?.tests_count > 0"
          class="rounded-lg border border-amber-300 dark:border-amber-700 p-3 flex justify-between items-center cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20"
          @click="loadNoVisitTests"
        >
          <div class="font-medium text-amber-800 dark:text-amber-200">No Visit</div>
          <div class="text-xs text-amber-700 dark:text-amber-300">
            {{ visitsOverview.no_visit.tests_count }} tests · {{ visitsOverview.no_visit.draft_tests_count }} draft
          </div>
        </div>
      </template>

      <div v-if="testsContext" class="bg-white dark:bg-gray-800 rounded-xl border p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
          <h4 class="font-semibold">{{ testsContext.visit_label }} — Tests</h4>
          <div v-if="selectedVisitId" class="flex flex-wrap gap-2">
            <template v-if="canReprintBill">
              <BaseButton
                v-for="bill in laboratoryBills"
                :key="bill.id"
                size="sm"
                variant="secondary"
                :loading="reprintingBillId === bill.id"
                @click="reprintBill(bill.id)"
              >
                Reprint Bill {{ bill.bill_no }}
              </BaseButton>
            </template>
            <BaseButton
              v-if="canPrintReports && hasPrintableTests"
              size="sm"
              variant="secondary"
              :loading="printingId === 'all'"
              @click="printAllReports"
            >
              Print All Reports
            </BaseButton>
          </div>
        </div>
        <BaseTable :columns="testColumns" :rows="testsContext.tests" :loading="loadingTests">
          <template #cell-status="{ row }">
            <span class="capitalize text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700">
              {{ (row.status || '').replace(/_/g, ' ') }}
            </span>
          </template>
          <template #cell-test_price="{ row }">{{ formatCurrency(row.test_price) }}</template>
          <template #cell-actions="{ row }">
            <div class="flex gap-1 flex-wrap">
              <BaseButton size="sm" variant="ghost" @click="$router.push(`/laboratory-results/${row.id}`)">View</BaseButton>
              <BaseButton
                v-if="authStore.can('edit laboratory results') || authStore.can('create laboratory results')"
                size="sm"
                variant="ghost"
                @click="openResultEntry(row)"
              >
                {{ row.status === 'draft' ? 'Enter Result' : 'Edit' }}
              </BaseButton>
              <BaseButton
                v-if="authStore.can('print laboratory results') && row.status !== 'draft'"
                size="sm"
                variant="ghost"
                :loading="printingId === row.id"
                @click="printResult(row)"
              >
                Print
              </BaseButton>
            </div>
          </template>
        </BaseTable>
      </div>
    </div>

    <LaboratoryResultPrintModal v-model="showPrintModal" :print-data="printData" />
    <LaboratoryBillPrintModal v-model="showBillPrintModal" :print-data="billPrintData" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { laboratoryBillService } from '@/services/laboratoryBillService';
import { formatCurrency } from '@/utils/formatters';
import LaboratoryResultPrintModal from '@/components/laboratory/LaboratoryResultPrintModal.vue';
import LaboratoryBillPrintModal from '@/components/laboratory/LaboratoryBillPrintModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import BaseTable from '@/components/ui/BaseTable.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const route = useRoute();
const router = useRouter();

const search = ref('');
const { flush: flushSearch } = useAutoSearch(search, loadPatients);
const patients = ref([]);
const loadingPatients = ref(false);
const selectedPatientId = ref(null);
const visitsOverview = ref(null);
const loadingVisits = ref(false);
const testsContext = ref(null);
const selectedVisitId = ref(null);
const loadingTests = ref(false);
const showPrintModal = ref(false);
const printData = ref(null);
const printingId = ref(null);
const showBillPrintModal = ref(false);
const billPrintData = ref(null);
const reprintingBillId = ref(null);

const canPrintReports = computed(() => authStore.can('print laboratory results'));
const canReprintBill = computed(() => authStore.can('print lab bills'));
const laboratoryBills = computed(() => testsContext.value?.laboratory_bills ?? []);
const hasPrintableTests = computed(() =>
  (testsContext.value?.tests ?? []).some((test) => ['completed', 'verified'].includes(test.status))
);

const patientColumns = [
  { key: 'patient_name', label: 'Patient' },
  { key: 'patient_code', label: 'MR#' },
  { key: 'phone', label: 'Phone' },
  { key: 'visits_count', label: 'Visits w/ Tests' },
  { key: 'no_visit_tests_count', label: 'No-Visit Tests' },
  { key: 'tests_count', label: 'Total Tests' },
  { key: 'draft_tests_count', label: 'Draft' },
  { key: 'actions', label: '' },
];

const testColumns = [
  { key: 'test_name', label: 'Test' },
  { key: 'status', label: 'Status' },
  { key: 'bill_no', label: 'Bill#' },
  { key: 'test_price', label: 'Price' },
  { key: 'created_at', label: 'Created' },
  { key: 'actions', label: '' },
];

async function loadPatients() {
  loadingPatients.value = true;
  try {
    const { data } = await laboratoryResultService.getPatientsOverview({
      search: search.value || undefined,
    });
    patients.value = data.data ?? [];
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load patients.');
    patients.value = [];
  } finally {
    loadingPatients.value = false;
  }
}

function openPatient(patientId) {
  selectedPatientId.value = patientId;
  testsContext.value = null;
  router.replace({ query: { patient_id: patientId } });
  loadPatientVisits(patientId);
}

function backToPatients() {
  selectedPatientId.value = null;
  visitsOverview.value = null;
  testsContext.value = null;
  selectedVisitId.value = null;
  router.replace({ query: {} });
}

async function loadPatientVisits(patientId) {
  loadingVisits.value = true;
  try {
    const { data } = await laboratoryResultService.getPatientVisitsOverview(patientId);
    visitsOverview.value = data;

    if (data.visits?.length === 1 && !(data.no_visit?.tests_count > 0)) {
      await loadVisitTests(data.visits[0].id);
    } else if (!data.visits?.length && data.no_visit?.tests_count > 0) {
      await loadNoVisitTests();
    }
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load visits.');
  } finally {
    loadingVisits.value = false;
  }
}

async function loadVisitTests(visitId) {
  selectedVisitId.value = visitId;
  loadingTests.value = true;
  try {
    const { data } = await laboratoryResultService.getVisitTests(selectedPatientId.value, visitId);
    testsContext.value = data;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load tests.');
  } finally {
    loadingTests.value = false;
  }
}

async function loadNoVisitTests() {
  selectedVisitId.value = null;
  loadingTests.value = true;
  try {
    const { data } = await laboratoryResultService.getNoVisitTests(selectedPatientId.value);
    testsContext.value = data;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load tests.');
  } finally {
    loadingTests.value = false;
  }
}

function openResultEntry(row) {
  if (row.status === 'draft' && authStore.can('create laboratory results')) {
    router.push({ path: '/laboratory-results/create', query: { result_id: row.id } });
    return;
  }

  router.push(`/laboratory-results/${row.id}/edit`);
}

async function printResult(row) {
  printingId.value = row.id;
  try {
    const { data } = await laboratoryResultService.getPrintData(row.id);
    printData.value = data.print_data;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Print failed.');
  } finally {
    printingId.value = null;
  }
}

async function printAllReports() {
  if (!selectedVisitId.value) return;

  printingId.value = 'all';
  try {
    const { data } = await laboratoryResultService.getVisitPrintData(selectedVisitId.value);
    printData.value = data.print_data ?? null;

    if (!printData.value?.laboratory_results?.length) {
      toastStore.error('No completed laboratory reports to print for this visit.');
      return;
    }

    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Print failed.');
  } finally {
    printingId.value = null;
  }
}

async function reprintBill(billId) {
  reprintingBillId.value = billId;
  try {
    const { data } = await laboratoryBillService.getPrintData(billId);
    billPrintData.value = data.print_data;
    showBillPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load bill for printing.');
  } finally {
    reprintingBillId.value = null;
  }
}

onMounted(async () => {
  await loadPatients();
  if (route.query.patient_id) {
    openPatient(Number(route.query.patient_id));
  }
});
</script>
