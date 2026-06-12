<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Patients</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Basic personal patient information</p>
      </div>
      <BaseButton v-if="authStore.can('create patients')" @click="$router.push('/patients/create')">
        + Create Patient
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <BaseInput v-model="filters.search" placeholder="Search MR, name, cell, or CNIC..." @keyup.enter="flushSearch" />
        <div class="sm:col-span-2 flex gap-2">
          <BaseButton variant="secondary" @click="fetchPatients">Search</BaseButton>
          <BaseButton v-if="filters.search" variant="ghost" @click="clearSearch">Clear</BaseButton>
        </div>
      </div>
    </div>

    <!-- Desktop table -->
    <BaseTable :columns="columns" :rows="patients" :loading="loading" class="hidden md:block">
      <template #cell-patient_gender="{ row }">{{ formatGender(row.patient_gender) }}</template>
      <template #cell-patient_age="{ row }">{{ displayPatientAge(row) }}</template>
      <template #cell-patient_address="{ row }">
        <span class="max-w-xs truncate block" :title="row.patient_address">{{ row.patient_address || '—' }}</span>
      </template>
      <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton v-if="authStore.can('edit patients')" variant="ghost" size="sm" @click="$router.push(`/patients/${row.id}/edit`)">Edit</BaseButton>
          <BaseButton v-if="authStore.can('delete patients')" variant="ghost" size="sm" @click="confirmDelete(row)">Delete</BaseButton>
        </div>
      </template>
    </BaseTable>

    <!-- Mobile cards -->
    <div class="md:hidden space-y-3">
      <div v-if="loading" class="h-32 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />
      <p v-else-if="!patients.length" class="text-center text-gray-500 py-8">No patients found.</p>
      <div
        v-for="patient in patients"
        :key="patient.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm"
      >
        <p class="font-semibold text-gray-900 dark:text-white">{{ patient.patient_name }}</p>
        <p class="text-xs text-teal-600 font-mono">{{ patient.mr_number }}</p>
        <p class="text-sm text-gray-500">{{ patient.patient_father_name || '—' }}</p>
        <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
          <p>Gender: {{ formatGender(patient.patient_gender) }}</p>
          <p>Age: {{ displayPatientAge(patient) }}</p>
          <p>Cell: {{ patient.patient_cell }}</p>
          <p>CNIC: {{ patient.patient_cnic || '—' }}</p>
          <p class="truncate">Address: {{ patient.patient_address || '—' }}</p>
        </div>
        <div class="flex gap-2 mt-3">
          <BaseButton v-if="authStore.can('edit patients')" variant="secondary" size="sm" @click="$router.push(`/patients/${patient.id}/edit`)">Edit</BaseButton>
          <BaseButton v-if="authStore.can('delete patients')" variant="ghost" size="sm" @click="confirmDelete(patient)">Delete</BaseButton>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4">
      <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Previous</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>

    <BaseModal v-model="deleteModal.open" title="Delete Patient" size="sm">
      <p class="text-gray-600 dark:text-gray-300">
        Delete patient <strong>{{ deleteModal.patient?.patient_name }}</strong>? This can be restored from the database if needed (soft delete).
      </p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deletePatient">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { displayPatientAge, formatDate, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import BaseModal from '@/components/ui/BaseModal.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

const columns = [
  { key: 'mr_number', label: 'MR No.' },
  { key: 'patient_name', label: 'Patient Name' },
  { key: 'patient_father_name', label: 'S/o, W/o, D/o' },
  { key: 'patient_gender', label: 'Gender' },
  { key: 'patient_age', label: 'Age' },
  { key: 'patient_cell', label: 'Cell' },
  { key: 'patient_cnic', label: 'CNIC' },
  { key: 'patient_address', label: 'Address' },
  { key: 'created_at', label: 'Created' },
  { key: 'actions', label: 'Actions' },
];

const patients = ref([]);
const loading = ref(true);
const filters = reactive({ search: '' });
const { flush: flushSearch } = useAutoSearch(() => filters.search, () => fetchPatients(1));
const pagination = reactive({ current_page: 1, last_page: 1 });
const deleteModal = reactive({ open: false, patient: null, deleting: false });

async function fetchPatients(page = 1) {
  loading.value = true;
  try {
    const { data } = await patientService.getPatients({
      search: filters.search || undefined,
      page,
    });
    patients.value = data.data;
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load patients.');
  } finally {
    loading.value = false;
  }
}

function clearSearch() {
  filters.search = '';
  fetchPatients();
}

function goPage(page) {
  fetchPatients(page);
}

function confirmDelete(patient) {
  deleteModal.patient = patient;
  deleteModal.open = true;
}

async function deletePatient() {
  deleteModal.deleting = true;
  try {
    await patientService.deletePatient(deleteModal.patient.id);
    toastStore.success('Patient deleted successfully.');
    deleteModal.open = false;
    fetchPatients(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to delete patient.');
  } finally {
    deleteModal.deleting = false;
  }
}

onMounted(() => fetchPatients());
</script>
