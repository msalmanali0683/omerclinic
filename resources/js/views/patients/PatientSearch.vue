<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Search Patient</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
          All patient visits in latest order. Each row is one visit; the same patient may appear multiple times.
        </p>
      </div>
      <BaseButton
        v-if="authStore.can('create patients')"
        @click="$router.push('/patients/create')"
      >
        Register New Patient
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <form class="flex flex-col sm:flex-row gap-3 sm:items-end" @submit.prevent="applySearch">
        <div class="flex-1">
          <BaseInput
            v-model="query"
            label="Search"
            placeholder="MR number, name, cell, or CNIC"
          />
        </div>
        <div class="flex gap-2">
          <BaseButton type="submit">Search</BaseButton>
          <BaseButton v-if="activeQuery" variant="secondary" type="button" @click="clearSearch">
            Clear
          </BaseButton>
        </div>
      </form>
    </div>

    <BaseTable :columns="columns" :rows="visits" :loading="loading">
      <template #cell-patient_name="{ row }">{{ row.patient?.patient_name ?? '—' }}</template>
      <template #cell-mr_number="{ row }">{{ row.patient?.mr_number ?? '—' }}</template>
      <template #cell-visit_date="{ row }">{{ formatDate(row.visit_date) }}</template>
      <template #cell-visit_time="{ row }">{{ formatVisitTime(row.visit_time) }}</template>
      <template #cell-token_number="{ row }">
        {{ row.has_token ? (row.token_display ?? row.token_number ?? '—') : '—' }}
      </template>
      <template #cell-status="{ row }">
        <span :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">
          {{ formatStatus(row.status) }}
        </span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap gap-1">
          <BaseButton
            v-if="authStore.can('view patient queue')"
            variant="ghost"
            size="sm"
            @click="$router.push(`/queue/${row.id}`)"
          >
            Open
          </BaseButton>
          <BaseButton
            v-if="authStore.can('view patient visits') && row.patient"
            variant="ghost"
            size="sm"
            @click="openVisitsDrawer(row.patient)"
          >
            Show Visits
          </BaseButton>
          <BaseButton
            v-if="authStore.can('add patient to queue') && row.patient && !row.patient.in_queue_today"
            variant="ghost"
            size="sm"
            @click="openAddToQueueModal(row.patient)"
          >
            Add to Queue
          </BaseButton>
          <BaseButton
            v-if="canReturnToPendingPrescription(row)"
            variant="ghost"
            size="sm"
            :loading="returningVisitId === row.id"
            @click="returnToPendingPrescription(row)"
          >
            Back to Doctor Queue
          </BaseButton>
          <BaseButton
            v-if="row.has_token && row.can_reprint_token"
            variant="ghost"
            size="sm"
            :loading="reprintingTokenId === row.token_id"
            @click="reprintToken(row)"
          >
            Reprint Token
          </BaseButton>
          <BaseButton
            v-if="authStore.can('edit patients') && row.patient"
            variant="ghost"
            size="sm"
            @click="$router.push(`/patients/${row.patient.id}/edit`)"
          >
            Edit
          </BaseButton>
        </div>
      </template>
    </BaseTable>

    <div v-if="error" class="mt-4 rounded-lg bg-red-50 p-4 text-red-700 dark:bg-red-900/20 dark:text-red-300">
      {{ error }}
    </div>

    <div v-if="!loading && !error && visits.length === 0" class="mt-4 text-center text-sm text-gray-500">
      {{ activeQuery ? 'No visits found for your search.' : 'No patient visits recorded yet.' }}
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4">
      <p class="text-sm text-gray-500">
        Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} visits)
      </p>
      <div class="flex gap-2">
        <BaseButton
          variant="secondary"
          size="sm"
          :disabled="pagination.current_page <= 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          Prev
        </BaseButton>
        <BaseButton
          variant="secondary"
          size="sm"
          :disabled="pagination.current_page >= pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          Next
        </BaseButton>
      </div>
    </div>

    <PatientVisitsDrawer v-model="visitsDrawerOpen" :patient="selectedPatient" />

    <AddPatientToQueueModal
      v-model="addToQueueModalOpen"
      :initial-patient="queueModalPatient"
      @added="handlePatientAddedToQueue"
    />

    <PatientTokenPrintModal v-model="showTokenPrintModal" :print-data="tokenPrintData" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { patientQueueService } from '@/services/patientQueueService';
import { patientVisitTokenService } from '@/services/patientVisitTokenService';
import { formatDate, formatVisitTime } from '@/utils/formatters';
import PatientVisitsDrawer from '@/components/patient-visits/PatientVisitsDrawer.vue';
import AddPatientToQueueModal from '@/components/queue/AddPatientToQueueModal.vue';
import PatientTokenPrintModal from '@/components/tokens/PatientTokenPrintModal.vue';
import { buildTokenPrintDataFromResponse, shouldOpenTokenPrintModal } from '@/utils/patientQueueToken';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const columns = [
  { key: 'patient_name', label: 'Patient Name' },
  { key: 'mr_number', label: 'MR Number' },
  { key: 'visit_date', label: 'Visit Date' },
  { key: 'visit_time', label: 'Visit Time' },
  { key: 'token_number', label: 'Token' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' },
];

const query = ref('');
const activeQuery = ref('');
const visits = ref([]);
const loading = ref(false);
const error = ref('');
const returningVisitId = ref(null);
const reprintingTokenId = ref(null);
const addToQueueModalOpen = ref(false);
const queueModalPatient = ref(null);
const showTokenPrintModal = ref(false);
const tokenPrintData = ref({});
const selectedPatient = ref(null);
const visitsDrawerOpen = ref(false);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

function isSameDayVisit(visitDate) {
  if (!visitDate) {
    return false;
  }

  const visit = new Date(visitDate);
  const today = new Date();

  return visit.getFullYear() === today.getFullYear()
    && visit.getMonth() === today.getMonth()
    && visit.getDate() === today.getDate();
}

function canReturnToPendingPrescription(row) {
  return authStore.can('return visit to pending prescription')
    && row.status === 'prescribed'
    && isSameDayVisit(row.visit_date);
}

function formatStatus(status) {
  if (!status) {
    return '—';
  }

  return String(status).replace(/_/g, ' ');
}

function statusClass(status) {
  const map = {
    waiting: 'bg-yellow-100 text-yellow-800',
    pending_prescription: 'bg-orange-100 text-orange-800',
    prescribed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };

  return map[status] ?? 'bg-gray-100 text-gray-800';
}

async function loadVisits(page = 1) {
  loading.value = true;
  error.value = '';

  try {
    const params = { page, per_page: 25 };

    if (activeQuery.value) {
      params.q = activeQuery.value;
    }

    const { data } = await patientService.searchPatientVisits(params);
    visits.value = data.data ?? [];
    pagination.value = {
      current_page: data.meta?.current_page ?? 1,
      last_page: data.meta?.last_page ?? 1,
      total: data.meta?.total ?? visits.value.length,
    };
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load visits.';
    visits.value = [];
  } finally {
    loading.value = false;
  }
}

function applySearch() {
  activeQuery.value = query.value.trim();
  loadVisits(1);
}

function clearSearch() {
  query.value = '';
  activeQuery.value = '';
  loadVisits(1);
}

function goToPage(page) {
  loadVisits(page);
}

function openVisitsDrawer(patient) {
  selectedPatient.value = patient;
  visitsDrawerOpen.value = true;
}

async function returnToPendingPrescription(row) {
  if (!row?.id || !canReturnToPendingPrescription(row)) {
    return;
  }

  const patientName = row.patient?.patient_name ?? 'this patient';
  if (!confirm(`Return ${patientName}'s visit to pending prescription and doctor queue?`)) {
    return;
  }

  returningVisitId.value = row.id;

  try {
    const { data } = await patientQueueService.returnToPendingPrescription(row.id);
    toastStore.success(data.message ?? 'Visit returned to doctor queue.');
    await loadVisits(pagination.value.current_page);
  } catch (err) {
    const errors = err.response?.data?.errors ?? {};
    toastStore.error(
      errors.status?.[0]
      ?? errors.visit_date?.[0]
      ?? err.response?.data?.message
      ?? 'Failed to return visit to doctor queue.'
    );
  } finally {
    returningVisitId.value = null;
  }
}

function openAddToQueueModal(patient) {
  if (!patient?.id) {
    return;
  }

  queueModalPatient.value = patient;
  addToQueueModalOpen.value = true;
}

function handlePatientAddedToQueue(data) {
  if (shouldOpenTokenPrintModal(data)) {
    tokenPrintData.value = buildTokenPrintDataFromResponse(data);
    showTokenPrintModal.value = true;
  }

  loadVisits(pagination.value.current_page);
}

async function reprintToken(row) {
  if (!row?.token_id) {
    return;
  }

  reprintingTokenId.value = row.token_id;

  try {
    const { data } = await patientVisitTokenService.reprintToken(row.token_id);
    tokenPrintData.value = data.print_data ?? {};
    showTokenPrintModal.value = true;
    toastStore.success(data.message ?? 'Token ready to reprint.');
  } catch (err) {
    toastStore.error(err.response?.data?.message ?? 'Failed to reprint token.');
  } finally {
    reprintingTokenId.value = null;
  }
}

onMounted(() => {
  loadVisits(1);
});

watch(addToQueueModalOpen, (open) => {
  if (!open) {
    queueModalPatient.value = null;
  }
});
</script>
