<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Patient Queue</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Patients pending for prescription</p>
      </div>
      <div class="flex gap-2">
        <BaseButton
          v-if="authStore.can('add patient to queue')"
          @click="openAddToQueueModal()"
        >
          Add to Queue
        </BaseButton>
        <BaseButton v-if="authStore.can('search patients')" variant="secondary" @click="$router.push('/patients/search')">
          Search Patient
        </BaseButton>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-3">
      <BaseInput v-model="filters.search" placeholder="MR or patient name..." @keyup.enter="fetchQueue" />
      <BaseSelect v-model="filters.status" :options="statusOptions" @change="fetchQueue" />
      <BaseSelect v-model="filters.doctor_id" placeholder="All doctors" :options="doctorOptions" @change="fetchQueue" />
      <BaseInput v-model="filters.visit_date" type="date" label="" @change="fetchQueue" />
    </div>

    <BaseTable :columns="columns" :rows="visits" :loading="loading">
      <template #cell-mr_number="{ row }">{{ row.patient?.mr_number }}</template>
      <template #cell-patient_name="{ row }">{{ row.patient?.patient_name }}</template>
      <template #cell-patient_father_name="{ row }">{{ row.patient?.patient_father_name || '—' }}</template>
      <template #cell-patient_gender="{ row }">{{ formatGender(row.patient?.patient_gender) }}</template>
      <template #cell-patient_age="{ row }">{{ displayPatientAge(row.patient) }}</template>
      <template #cell-patient_cell="{ row }">{{ row.patient?.patient_cell }}</template>
      <template #cell-patient_cnic="{ row }">{{ row.patient?.patient_cnic || '—' }}</template>
      <template #cell-doctor="{ row }">{{ row.doctor?.name || 'Unassigned' }}</template>
      <template #cell-status="{ row }">
        <span :class="statusClass(row.status)" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ formatStatus(row.status) }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap gap-1">
          <BaseButton variant="ghost" size="sm" @click="$router.push(`/queue/${row.id}`)">Open</BaseButton>
          <BaseButton v-if="authStore.can('cancel patient queue')" variant="ghost" size="sm" @click="cancelVisit(row)">Cancel</BaseButton>
        </div>
      </template>
    </BaseTable>

    <div v-if="pagination.last_page > 1" class="flex justify-between mt-4">
      <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Prev</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>

    <AddPatientToQueueModal
      v-model="addToQueueModalOpen"
      @added="handlePatientAddedToQueue"
    />

    <PatientTokenPrintModal v-model="showTokenPrintModal" :print-data="tokenPrintData" />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientQueueService } from '@/services/patientQueueService';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import { userService } from '@/services/userService';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import AddPatientToQueueModal from '@/components/queue/AddPatientToQueueModal.vue';
import { buildTokenPrintDataFromResponse, shouldOpenTokenPrintModal } from '@/utils/patientQueueToken';
import PatientTokenPrintModal from '@/components/tokens/PatientTokenPrintModal.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const columns = [
  { key: 'id', label: 'Visit #' },
  { key: 'mr_number', label: 'MR No.' },
  { key: 'patient_name', label: 'Patient' },
  { key: 'patient_father_name', label: 'S/o, W/o, D/o' },
  { key: 'patient_gender', label: 'Gender' },
  { key: 'patient_age', label: 'Age' },
  { key: 'patient_cell', label: 'Cell' },
  { key: 'patient_cnic', label: 'CNIC' },
  { key: 'visit_date', label: 'Date' },
  { key: 'doctor', label: 'Doctor' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions' },
];

const visits = ref([]);
const loading = ref(true);
const doctorOptions = ref([{ value: '', label: 'All doctors' }]);
const filters = reactive({ search: '', status: 'pending_prescription,in_consultation', doctor_id: '', visit_date: '' });
const pagination = reactive({ current_page: 1, last_page: 1 });
const addToQueueModalOpen = ref(false);
const showTokenPrintModal = ref(false);
const tokenPrintData = ref({});

const statusOptions = [
  { value: 'pending_prescription,in_consultation', label: 'Active (Pending + In Consultation)' },
  { value: 'pending_prescription', label: 'Pending Prescription' },
  { value: 'in_consultation', label: 'In Consultation' },
  { value: 'prescribed', label: 'Prescribed' },
  { value: 'cancelled', label: 'Cancelled' },
];

function formatStatus(s) {
  return s?.replace(/_/g, ' ') ?? '';
}

function statusClass(s) {
  return {
    pending_prescription: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    in_consultation: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    prescribed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-gray-100 text-gray-600',
  }[s] ?? 'bg-gray-100 text-gray-600';
}

async function fetchQueue(page = 1) {
  loading.value = true;
  try {
    const params = { page, status: filters.status };
    if (filters.search) params.search = filters.search;
    if (filters.doctor_id) params.doctor_id = filters.doctor_id;
    if (filters.visit_date) params.visit_date = filters.visit_date;
    const { data } = await patientQueueService.getQueue(params);
    visits.value = data.data;
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load queue.');
  } finally {
    loading.value = false;
  }
}

function goPage(p) { fetchQueue(p); }

function openAddToQueueModal() {
  addToQueueModalOpen.value = true;
}

function handlePatientAddedToQueue(data) {
  if (shouldOpenTokenPrintModal(data)) {
    tokenPrintData.value = buildTokenPrintDataFromResponse(data);
    showTokenPrintModal.value = true;
  }

  fetchQueue(pagination.current_page);
}

async function cancelVisit(row) {
  if (!confirm('Cancel this queue entry?')) return;
  try {
    await patientQueueService.cancelQueue(row.id);
    toastStore.success('Queue entry cancelled.');
    fetchQueue(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to cancel.');
  }
}

onMounted(async () => {
  await fetchQueue();
  if (authStore.can('assign doctor to queue')) {
    try {
      const { data } = await userService.listDoctors();
      doctorOptions.value = [{ value: '', label: 'All doctors' }, ...(data.data ?? []).map((u) => ({ value: u.id, label: u.name }))];
    } catch { /* optional */ }
  }
});
</script>
