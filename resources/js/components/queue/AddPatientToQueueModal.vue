<template>
  <BaseModal
    v-model="open"
    :title="step === 1 ? 'Select Patient' : 'Add Patient to Queue'"
    size="lg"
  >
    <div v-if="step === 1" class="space-y-4">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Search by MR number, patient name, cell, or CNIC, then select a patient to continue.
      </p>

      <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="flushSearch">
        <BaseInput
          v-model="searchQuery"
          placeholder="Search patient..."
          class="flex-1"
          @keyup.enter="flushSearch"
        />
        <BaseButton type="submit" variant="secondary" :loading="searchLoading">Search</BaseButton>
      </form>

      <div v-if="searchLoading" class="h-24 rounded-lg bg-gray-100 dark:bg-gray-700 animate-pulse" />

      <div
        v-else-if="searchResults.length"
        class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"
      >
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">MR#</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Patient</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Cell</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-500">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="patient in searchResults"
              :key="patient.id"
              :class="selectedPatient?.id === patient.id ? 'bg-teal-50 dark:bg-teal-900/20' : ''"
            >
              <td class="px-3 py-2 font-mono text-teal-600">{{ patient.mr_number || '—' }}</td>
              <td class="px-3 py-2">
                <div class="font-medium text-gray-900 dark:text-white">{{ patient.patient_name }}</div>
                <div class="text-xs text-gray-500">{{ patient.patient_father_name || '—' }}</div>
              </td>
              <td class="px-3 py-2">{{ patient.patient_cell || '—' }}</td>
              <td class="px-3 py-2">
                <BaseButton size="sm" variant="ghost" @click="selectPatient(patient)">
                  {{ selectedPatient?.id === patient.id ? 'Selected' : 'Select' }}
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else-if="searchedOnce" class="text-sm text-gray-500">No patients found.</p>
    </div>

    <div v-else class="space-y-4">
      <div class="rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20 p-4">
        <p class="text-xs uppercase tracking-wide text-teal-700 dark:text-teal-300">Selected Patient</p>
        <p class="font-mono text-teal-600 mt-1">{{ selectedPatient?.mr_number || '—' }}</p>
        <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">{{ selectedPatient?.patient_name }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          {{ selectedPatient?.patient_father_name || '—' }} · {{ selectedPatient?.patient_cell || '—' }}
        </p>
      </div>

      <BaseSelect
        v-model="doctorId"
        label="Assign Doctor"
        placeholder="Select doctor"
        :options="doctorOptions"
        required
      />

      <p class="text-sm text-gray-500 dark:text-gray-400">
        Confirm to add this patient to today&apos;s queue.
      </p>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="close">{{ step === 1 ? 'Cancel' : 'Back' }}</BaseButton>
      <BaseButton
        v-if="step === 1"
        :disabled="!selectedPatient"
        @click="goToConfirmStep"
      >
        Next
      </BaseButton>
      <BaseButton
        v-else
        :loading="submitting"
        @click="submitAddToQueue"
      >
        Add to Queue
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { patientQueueService } from '@/services/patientQueueService';
import { userService } from '@/services/userService';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import { useAutoSearch } from '@/composables/useAutoSearch';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  initialPatient: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'added']);

const authStore = useAuthStore();
const toastStore = useToastStore();

const step = ref(1);
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchedOnce = ref(false);
const { flush: flushSearch } = useAutoSearch(searchQuery, searchPatients, { minLength: 1 });
const selectedPatient = ref(null);
const doctorId = ref('');
const doctorOptions = ref([]);
const submitting = ref(false);
const doctorsLoaded = ref(false);

const open = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

watch(
  () => props.modelValue,
  async (visible) => {
    if (!visible) {
      return;
    }

    resetState();

    if (props.initialPatient?.id) {
      selectedPatient.value = { ...props.initialPatient };
      step.value = 2;
    }

    await loadDoctors();
  },
);

watch(
  () => props.initialPatient,
  (patient) => {
    if (!open.value || !patient?.id) {
      return;
    }

    selectedPatient.value = { ...patient };
    step.value = 2;
  },
);

function resetState() {
  step.value = 1;
  searchQuery.value = '';
  searchResults.value = [];
  searchLoading.value = false;
  searchedOnce.value = false;
  selectedPatient.value = props.initialPatient?.id ? { ...props.initialPatient } : null;
  doctorId.value = '';
  submitting.value = false;
  applyDefaultDoctor();
}

async function loadDoctors() {
  if (doctorsLoaded.value || !authStore.can('add patient to queue')) {
    applyDefaultDoctor();
    return;
  }

  try {
    const { data } = await userService.listDoctors();
    doctorOptions.value = (data.data ?? []).map((user) => ({ value: user.id, label: user.name }));
    doctorsLoaded.value = true;
    applyDefaultDoctor();
  } catch {
    doctorOptions.value = [];
  }
}

function applyDefaultDoctor() {
  if (doctorOptions.value.length && !doctorId.value) {
    doctorId.value = doctorOptions.value[0].value;
  }
}

async function searchPatients() {
  const query = searchQuery.value.trim();

  if (!query) {
    searchResults.value = [];
    searchedOnce.value = false;
    return;
  }

  searchLoading.value = true;
  searchedOnce.value = true;

  try {
    const { data } = await patientService.searchPatients(query);
    searchResults.value = data.data ?? [];
  } catch (error) {
    searchResults.value = [];
    toastStore.error(error.response?.data?.message ?? 'Failed to search patients.');
  } finally {
    searchLoading.value = false;
  }
}

function selectPatient(patient) {
  selectedPatient.value = patient;
}

function goToConfirmStep() {
  if (!selectedPatient.value?.id) {
    toastStore.error('Select a patient to continue.');
    return;
  }

  step.value = 2;
}

function close() {
  if (step.value === 2) {
    step.value = 1;
    return;
  }

  open.value = false;
}

async function submitAddToQueue() {
  if (!selectedPatient.value?.id) {
    toastStore.error('Select a patient to continue.');
    step.value = 1;
    return;
  }

  if (!doctorId.value) {
    toastStore.error('Select a doctor to continue.');
    return;
  }

  submitting.value = true;

  try {
    const { data } = await patientQueueService.addToQueue(selectedPatient.value.id, {
      doctor_id: doctorId.value,
    });
    toastStore.success(data.message ?? 'Patient added to queue.');
    emit('added', data);
    open.value = false;
  } catch (error) {
    toastStore.error(error.response?.data?.message ?? 'Failed to add patient to queue.');
  } finally {
    submitting.value = false;
  }
}
</script>
