<template>
  <div class="max-w-7xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Register New Patient</h2>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
        {{ isDoctor ? 'New patients are added to your queue automatically' : 'A unique MR number will be assigned automatically' }}
      </p>
    </div>

    <div
      v-if="!isDoctor && duplicateInfo"
      class="mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700"
    >
      <p class="text-sm text-amber-800 dark:text-amber-200">{{ duplicateInfo.message }}</p>
      <p v-if="duplicateInfo.patient" class="text-sm mt-1 font-mono">MR: {{ duplicateInfo.patient.mr_number }}</p>
      <div class="flex gap-2 mt-3">
        <BaseButton v-if="authStore.can('add patient to queue')" size="sm" @click="openAddExistingToQueueModal">Add to Queue</BaseButton>
        <BaseButton v-if="duplicateInfo.code === 'possible_duplicate'" variant="secondary" size="sm" @click="forceCreate">Create Anyway</BaseButton>
        <BaseButton variant="ghost" size="sm" @click="duplicateInfo = null">Dismiss</BaseButton>
      </div>
    </div>

    <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInput v-model="form.patient_name" label="Patient Name" :error="errors.patient_name" required />
        <BaseInput v-model="form.patient_father_name" label="Father Name" :error="errors.patient_father_name" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseSelect
          v-model="form.patient_gender"
          label="Gender"
          placeholder="Select gender"
          :options="genderOptions"
          :error="errors.patient_gender"
          required
        />
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Age
            <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-5 gap-2">
            <div class="col-span-3">
              <BaseInput
                v-model="form.patient_age"
                type="number"
                min="0"
                max="150"
                :error="errors.patient_age"
                required
              />
            </div>
            <div class="col-span-2">
              <BaseSelect
                v-model="form.patient_age_unit"
                :options="ageUnitOptions"
                :error="errors.patient_age_unit"
                required
              />
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <BaseInput v-model="form.patient_cell" label="Cell Number" :error="errors.patient_cell" required />
        <BaseInput v-model="form.patient_cnic" label="CNIC" hint="e.g. 35202-1234567-1" :error="errors.patient_cnic" />
        <div class="md:col-span-2 lg:col-span-1">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
          <textarea
            v-model="form.patient_address"
            rows="2"
            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:outline-none"
            :class="errors.patient_address ? 'border-red-500 focus:ring-red-500' : ''"
          />
          <p v-if="errors.patient_address" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.patient_address }}</p>
        </div>
      </div>

      <div v-if="isDoctor" class="p-3 rounded-lg bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800">
        <p class="text-sm text-teal-800 dark:text-teal-200">
          This patient will be automatically added to your queue with status pending prescription.
        </p>
      </div>

      <template v-else-if="canAddToQueue">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Patient will be registered and added to the selected doctor&apos;s queue.
        </p>

        <BaseSelect
          v-model="form.doctor_id"
          label="Assign Doctor"
          placeholder="Select doctor"
          :options="doctorOptions"
          :error="errors.doctor_id"
          required
        />
      </template>

      <div class="flex gap-3 pt-2">
        <BaseButton type="submit" :loading="saving">{{ isDoctor ? 'Register & Add to My Queue' : 'Register Patient' }}</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>

    <section v-if="canViewPatientList" class="mt-10 pt-8 border-t border-gray-200 dark:border-gray-700">
      <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Registered Patients</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Recent and searchable patient records</p>
      </div>

      <PatientListTable
        v-model:search-query="searchQuery"
        :patients="patients"
        :loading="patientsLoading"
        :error="patientsError"
        :pagination="pagination"
        @search="handlePatientSearch"
        @page-change="loadPatients"
        @edit="editPatient"
        @add-to-queue="addToQueue"
        @show-visits="showVisits"
        @delete="confirmDelete"
      />
    </section>

    <PatientVisitsDrawer v-model="visitsOpen" :patient="selectedPatient" />

    <PatientTokenPrintModal v-model="showTokenPrintModal" :print-data="tokenPrintData" />

    <AddPatientToQueueModal
      v-model="addToQueueModalOpen"
      :initial-patient="queueModalPatient"
      @added="handlePatientAddedToQueue"
    />

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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { userService } from '@/services/userService';
import { useFormErrors } from '@/composables/useFormErrors';
import { AGE_UNIT_OPTIONS, GENDER_OPTIONS } from '@/utils/formatters';
import PatientListTable from '@/components/patients/PatientListTable.vue';
import PatientVisitsDrawer from '@/components/patient-visits/PatientVisitsDrawer.vue';
import PatientTokenPrintModal from '@/components/tokens/PatientTokenPrintModal.vue';
import AddPatientToQueueModal from '@/components/queue/AddPatientToQueueModal.vue';
import { buildTokenPrintDataFromResponse, shouldOpenTokenPrintModal } from '@/utils/patientQueueToken';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseModal from '@/components/ui/BaseModal.vue';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({
  patient_name: '',
  patient_father_name: '',
  patient_gender: '',
  patient_age: '',
  patient_age_unit: 'years',
  patient_cell: '',
  patient_address: '',
  patient_cnic: '',
  doctor_id: '',
  force_create: false,
});

const genderOptions = GENDER_OPTIONS;
const ageUnitOptions = AGE_UNIT_OPTIONS;

const saving = ref(false);
const duplicateInfo = ref(null);
const doctorOptions = ref([]);
const isDoctor = computed(() => authStore.hasRole('doctor'));
const canAddToQueue = computed(() => authStore.can('add patient to queue'));

const canViewPatientList = computed(() =>
  authStore.can('view patients')
  || authStore.can('view limited patient info')
  || authStore.can('search patients')
);

const patients = ref([]);
const patientsLoading = ref(false);
const patientsError = ref('');
const searchQuery = ref('');
const pagination = reactive({ current_page: 1, last_page: 1 });
const visitsOpen = ref(false);
const selectedPatient = ref(null);
const deleteModal = reactive({ open: false, patient: null, deleting: false });
const showTokenPrintModal = ref(false);
const tokenPrintData = ref({});
const addToQueueModalOpen = ref(false);
const queueModalPatient = ref(null);

function maybeOpenTokenPrintModal(data) {
  if (!shouldOpenTokenPrintModal(data)) {
    return;
  }

  tokenPrintData.value = buildTokenPrintDataFromResponse(data);
  showTokenPrintModal.value = true;
}

function openAddToQueueModal(patient) {
  if (!patient?.id) {
    return;
  }

  queueModalPatient.value = patient;
  addToQueueModalOpen.value = true;
}

function openAddExistingToQueueModal() {
  if (!duplicateInfo.value?.patient?.id) {
    return;
  }

  openAddToQueueModal(duplicateInfo.value.patient);
}

function handlePatientAddedToQueue(data) {
  maybeOpenTokenPrintModal(data);
  duplicateInfo.value = null;
  refreshPatientList();
}

function buildPayload() {
  const payload = {
    patient_name: form.patient_name,
    patient_father_name: form.patient_father_name,
    patient_gender: form.patient_gender,
    patient_age: form.patient_age !== '' ? Number(form.patient_age) : undefined,
    patient_age_unit: form.patient_age_unit,
    patient_cell: form.patient_cell,
    patient_address: form.patient_address,
    patient_cnic: form.patient_cnic,
  };

  if (!isDoctor.value && canAddToQueue.value) {
    payload.doctor_id = form.doctor_id;
    payload.force_create = form.force_create ? 1 : 0;
  } else if (!isDoctor.value) {
    payload.force_create = form.force_create ? 1 : 0;
  }

  return payload;
}

function resetForm() {
  form.patient_name = '';
  form.patient_father_name = '';
  form.patient_gender = '';
  form.patient_age = '';
  form.patient_age_unit = 'years';
  form.patient_cell = '';
  form.patient_address = '';
  form.patient_cnic = '';
  form.doctor_id = doctorOptions.value[0]?.value ?? '';
  form.force_create = false;
}

function applyDefaultDoctor() {
  if (canAddToQueue.value && doctorOptions.value.length && !form.doctor_id) {
    form.doctor_id = doctorOptions.value[0].value;
  }
}

async function loadPatients(page = 1) {
  if (!canViewPatientList.value) return;

  patientsLoading.value = true;
  patientsError.value = '';

  try {
    const { data } = await patientService.getPatients({
      search: searchQuery.value.trim() || undefined,
      page,
      per_page: 10,
    });
    patients.value = data.data ?? [];
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
  } catch (e) {
    patientsError.value = e.response?.data?.message ?? 'Failed to load patients.';
    toastStore.error(patientsError.value);
  } finally {
    patientsLoading.value = false;
  }
}

function handlePatientSearch(query) {
  searchQuery.value = query ?? '';
  loadPatients(1);
}

function refreshPatientList() {
  return loadPatients(1);
}

async function submit() {
  clearErrors();
  duplicateInfo.value = null;
  saving.value = true;
  try {
    const { data } = await patientService.createPatient(buildPayload());
    toastStore.success(data.message ?? 'Patient registered successfully.');
    maybeOpenTokenPrintModal(data);
    resetForm();
    clearErrors();

    if (canViewPatientList.value) {
      searchQuery.value = '';
      await refreshPatientList();
    }
  } catch (e) {
    const data = e.response?.data;
    if (!isDoctor.value && e.response?.status === 409 && data?.patient) {
      duplicateInfo.value = { message: data.message, patient: data.patient, code: data.code };
    } else {
      setErrors(e);
      toastStore.error(data?.message ?? 'Failed to register patient.');
    }
  } finally {
    saving.value = false;
    form.force_create = false;
  }
}

function forceCreate() {
  form.force_create = true;
  submit();
}

async function addExistingToQueue() {
  openAddExistingToQueueModal();
}

function editPatient(patient) {
  router.push(`/patients/${patient.id}/edit`);
}

function showVisits(patient) {
  selectedPatient.value = patient;
  visitsOpen.value = true;
}

async function addToQueue(patient) {
  openAddToQueueModal(patient);
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
    await loadPatients(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to delete patient.');
  } finally {
    deleteModal.deleting = false;
  }
}

onMounted(async () => {
  if (canViewPatientList.value) {
    await loadPatients();
  }

  if (!canAddToQueue.value || isDoctor.value) {
    return;
  }

  try {
    const { data } = await userService.listDoctors();
    doctorOptions.value = (data.data ?? []).map((u) => ({ value: u.id, label: u.name }));
    applyDefaultDoctor();
  } catch { /* optional */ }
});

watch(addToQueueModalOpen, (open) => {
  if (!open) {
    queueModalPatient.value = null;
  }
});
</script>
