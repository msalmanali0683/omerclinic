<template>
  <div class="max-w-7xl">
    <div
      v-if="!isDoctor && duplicateInfo"
      class="mb-4 rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-4 shadow-sm dark:border-amber-700 dark:from-amber-900/20 dark:to-orange-900/10"
    >
      <p class="text-sm font-medium text-amber-900 dark:text-amber-200">{{ duplicateInfo.message }}</p>
      <p v-if="duplicateInfo.patient" class="mt-1 font-mono text-sm text-amber-800 dark:text-amber-300">MR: {{ duplicateInfo.patient.mr_number }}</p>
      <div class="mt-3 flex flex-wrap gap-2">
        <BaseButton v-if="authStore.can('add patient to queue')" size="sm" @click="openAddExistingToQueueModal">Add to Queue</BaseButton>
        <BaseButton v-if="duplicateInfo.code === 'possible_duplicate'" variant="secondary" size="sm" @click="forceCreate">Create Anyway</BaseButton>
        <BaseButton variant="ghost" size="sm" @click="duplicateInfo = null">Dismiss</BaseButton>
      </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-md dark:border-emerald-900/50 dark:bg-gray-800">
      <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-5 py-4 text-white">
        <div class="flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
            <AppIcon name="user-plus" class-name="w-6 h-6 text-white" />
          </span>
          <div>
            <h2 class="text-xl font-bold leading-tight sm:text-2xl">Register New Patient</h2>
            <p class="text-sm text-white/85">
              {{ isDoctor ? 'New patients are added to your queue automatically' : 'A unique MR number will be assigned automatically' }}
            </p>
          </div>
        </div>
      </div>

      <form class="space-y-5 p-5" @submit.prevent="submit">
        <PatientFormFields :form="form" :errors="errors">
          <template #after-fields>
            <div v-if="isDoctor" class="rounded-2xl border border-teal-200 bg-gradient-to-br from-teal-50 to-cyan-50 p-4 dark:border-teal-800 dark:from-teal-950/30 dark:to-cyan-950/20">
              <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-200">
                  <AppIcon name="queue" class-name="w-5 h-5" />
                </span>
                <p class="text-sm text-teal-800 dark:text-teal-200">
                  This patient will be automatically added to your queue with status pending prescription.
                </p>
              </div>
            </div>

            <div v-else-if="canAddToQueue" class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('amber').card">
              <PatientFormFieldHeader title="Assign Doctor" subtitle="Select consulting doctor" color="amber" required />
              <select v-model="form.doctor_id" :class="getPatientFieldStyle('amber').input">
                <option value="" disabled>Select doctor</option>
                <option v-for="opt in doctorOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <PatientFormFieldError :message="fieldError('doctor_id')" />
            </div>
          </template>
        </PatientFormFields>

        <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
          <BaseButton type="submit" class="min-w-[160px]" :loading="saving">
            {{ isDoctor ? 'Register & Add to My Queue' : 'Register Patient' }}
          </BaseButton>
          <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
        </div>
      </form>
    </div>

    <section v-if="canViewPatientList" class="mt-10 border-t border-gray-200 pt-8 dark:border-gray-700">
      <div class="mb-6 flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200">
          <AppIcon name="patients" class-name="w-5 h-5" />
        </span>
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Registered Patients</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Recent and searchable patient records</p>
        </div>
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

    <RegisterPatientVitalsModal
      v-model="showRegisterVitalsModal"
      :patient-id="registerVitalsContext.patientId"
      :visit-id="registerVitalsContext.visitId"
      :patient-name="registerVitalsContext.patientName"
      :mr-number="registerVitalsContext.mrNumber"
      @saved="onRegisterVitalsSaved"
      @closed="onRegisterVitalsClosed"
      @error="onRegisterVitalsError"
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
import { getPatientFieldStyle } from '@/utils/patientFieldTheme';
import PatientFormFields from '@/components/patients/PatientFormFields.vue';
import PatientFormFieldHeader from '@/components/patients/PatientFormFieldHeader.vue';
import PatientFormFieldError from '@/components/patients/PatientFormFieldError.vue';
import PatientListTable from '@/components/patients/PatientListTable.vue';
import PatientVisitsDrawer from '@/components/patient-visits/PatientVisitsDrawer.vue';
import PatientTokenPrintModal from '@/components/tokens/PatientTokenPrintModal.vue';
import AddPatientToQueueModal from '@/components/queue/AddPatientToQueueModal.vue';
import RegisterPatientVitalsModal from '@/components/vitals/RegisterPatientVitalsModal.vue';
import { buildTokenPrintDataFromResponse, shouldOpenTokenPrintModal } from '@/utils/patientQueueToken';
import AppIcon from '@/components/ui/AppIcon.vue';
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

const saving = ref(false);
const duplicateInfo = ref(null);
const doctorOptions = ref([]);
const isDoctor = computed(() => authStore.hasRole('doctor'));
const canAddToQueue = computed(() => authStore.can('add patient to queue'));

function fieldError(key) {
  const err = errors[key];

  return Array.isArray(err) ? err[0] : (err ?? '');
}

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
const showRegisterVitalsModal = ref(false);
const pendingRegistrationData = ref(null);
const registerVitalsContext = reactive({
  patientId: null,
  visitId: null,
  patientName: '',
  mrNumber: '',
});

const canRecordVitalsOnRegistration = computed(() =>
  authStore.can('record vitals on patient registration')
  && authStore.can('create patient vitals')
);

function maybeOpenTokenPrintModal(data) {
  if (!shouldOpenTokenPrintModal(data)) {
    return;
  }

  tokenPrintData.value = buildTokenPrintDataFromResponse(data);
  showTokenPrintModal.value = true;
}

function shouldOpenRegisterVitalsModal(data) {
  return canRecordVitalsOnRegistration.value
    && data?.patient?.id
    && data?.visit?.id;
}

function openRegisterVitalsModal(data) {
  pendingRegistrationData.value = data;
  registerVitalsContext.patientId = data.patient.id;
  registerVitalsContext.visitId = data.visit.id;
  registerVitalsContext.patientName = data.patient.patient_name ?? '';
  registerVitalsContext.mrNumber = data.patient.mr_number ?? '';
  showRegisterVitalsModal.value = true;
}

async function completeRegistration(data) {
  if (!data) {
    return;
  }

  maybeOpenTokenPrintModal(data);
  resetForm();
  clearErrors();
  pendingRegistrationData.value = null;

  if (canViewPatientList.value) {
    searchQuery.value = '';
    await refreshPatientList();
  }
}

async function onRegisterVitalsSaved() {
  const data = pendingRegistrationData.value;
  toastStore.success('Vitals recorded successfully.');
  showRegisterVitalsModal.value = false;
  await completeRegistration(data);
}

async function onRegisterVitalsClosed() {
  const data = pendingRegistrationData.value;
  showRegisterVitalsModal.value = false;
  await completeRegistration(data);
}

function onRegisterVitalsError(e) {
  toastStore.error(e?.response?.data?.message ?? 'Unable to save vitals.');
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

    if (shouldOpenRegisterVitalsModal(data)) {
      openRegisterVitalsModal(data);
    } else {
      await completeRegistration(data);
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
