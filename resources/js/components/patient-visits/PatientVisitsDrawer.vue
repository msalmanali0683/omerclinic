<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/40" @click="close" />
        <div class="relative w-full max-w-5xl h-full bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-700 flex flex-col">
          <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Patient Visits</h3>
              <p v-if="patient" class="text-sm text-gray-500">
                {{ patient.mr_number }} · {{ patient.patient_name }} · {{ formatGender(patient.patient_gender) }} · {{ displayPatientAge(patient) }}
              </p>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-600" @click="close">✕</button>
          </div>

          <div class="flex-1 overflow-hidden grid grid-cols-1 lg:grid-cols-2">
            <div class="border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-700 overflow-y-auto p-4">
              <PatientVisitsList
                :visits="visits"
                :loading="visitsLoading"
                :selected-visit-id="selectedVisit?.id"
                @select="selectVisit"
              />
            </div>
            <div class="overflow-y-auto p-4">
              <PatientVisitDetails
                :details="visitDetails"
                :loading="detailsLoading"
                :error="detailsError"
              />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useToastStore } from '@/stores/toast';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import { patientVisitHistoryService } from '@/services/patientVisitHistoryService';
import PatientVisitsList from '@/components/patient-visits/PatientVisitsList.vue';
import PatientVisitDetails from '@/components/patient-visits/PatientVisitDetails.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  patient: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue']);

const toastStore = useToastStore();
const visits = ref([]);
const visitsLoading = ref(false);
const selectedVisit = ref(null);
const visitDetails = ref(null);
const detailsLoading = ref(false);
const detailsError = ref('');

function close() {
  emit('update:modelValue', false);
}

async function loadVisits() {
  if (!props.patient?.id) return;
  visitsLoading.value = true;
  selectedVisit.value = null;
  visitDetails.value = null;
  detailsError.value = '';
  try {
    const { data } = await patientVisitHistoryService.getPatientVisits(props.patient.id);
    visits.value = data.data ?? [];
  } catch (e) {
    visits.value = [];
    toastStore.error(e.response?.data?.message ?? 'Failed to load visits.');
    if (e.response?.status === 403) close();
  } finally {
    visitsLoading.value = false;
  }
}

async function selectVisit(visit) {
  selectedVisit.value = visit;
  detailsLoading.value = true;
  detailsError.value = '';
  visitDetails.value = null;
  try {
    const { data } = await patientVisitHistoryService.getPatientVisitDetails(props.patient.id, visit.id);
    visitDetails.value = data;
  } catch (e) {
    detailsError.value = e.response?.data?.message ?? 'Failed to load visit details.';
    if (e.response?.status === 403) detailsError.value = 'You are not authorized to view this visit.';
  } finally {
    detailsLoading.value = false;
  }
}

watch(
  () => [props.modelValue, props.patient?.id],
  ([open]) => {
    if (open) loadVisits();
  }
);
</script>

<style scoped>
.drawer-enter-active, .drawer-leave-active { transition: opacity 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
</style>
