<template>
  <div>
    <div v-if="loading" class="space-y-4">
      <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse" />
      <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-lg animate-pulse" />
    </div>

    <div v-else-if="error" class="text-sm text-red-600 py-6 text-center">{{ error }}</div>

    <div v-else-if="details" class="space-y-6">
      <section class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Patient Information</h4>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
          <div><dt class="text-gray-500 inline">MR:</dt> {{ details.patient?.mr_number }}</div>
          <div><dt class="text-gray-500 inline">Name:</dt> {{ details.patient?.patient_name }}</div>
          <div><dt class="text-gray-500 inline">Gender:</dt> {{ formatGender(details.patient?.patient_gender) }}</div>
          <div><dt class="text-gray-500 inline">Age:</dt> {{ displayPatientAge(details.patient) }}</div>
          <div v-if="details.patient?.patient_father_name"><dt class="text-gray-500 inline">Father:</dt> {{ details.patient.patient_father_name }}</div>
          <div v-if="details.patient?.patient_cell"><dt class="text-gray-500 inline">Cell:</dt> {{ details.patient.patient_cell }}</div>
          <div v-if="details.patient?.patient_cnic"><dt class="text-gray-500 inline">CNIC:</dt> {{ details.patient.patient_cnic }}</div>
          <div v-if="details.patient?.patient_address" class="sm:col-span-2"><dt class="text-gray-500 inline">Address:</dt> {{ details.patient.patient_address }}</div>
        </dl>
      </section>

      <section class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Visit Information</h4>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
          <div><dt class="text-gray-500 inline">Date:</dt> {{ details.visit?.visit_date }}</div>
          <div><dt class="text-gray-500 inline">Time:</dt> {{ details.visit?.visit_time || '—' }}</div>
          <div><dt class="text-gray-500 inline">Doctor:</dt> {{ details.visit?.doctor?.name || 'Unassigned' }}</div>
          <div><dt class="text-gray-500 inline">Status:</dt> {{ formatStatus(details.visit?.status) }}</div>
          <div v-if="details.visit?.reason_for_visit" class="sm:col-span-2"><dt class="text-gray-500 inline">Reason:</dt> {{ details.visit.reason_for_visit }}</div>
        </dl>
      </section>

      <section v-if="canShowVitals" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Vitals</h4>
        <div v-if="details.vitals" class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
          <div>BP: {{ details.vitals.blood_pressure || '—' }}</div>
          <div>Temp: {{ details.vitals.temperature || '—' }}</div>
          <div>Pulse: {{ details.vitals.pulse_rate || '—' }}</div>
          <div>RR: {{ details.vitals.respiratory_rate || '—' }}</div>
          <div>Weight: {{ details.vitals.weight || '—' }}</div>
        </div>
        <p v-else class="text-sm text-gray-500">No vitals recorded for this visit.</p>
      </section>

      <section v-if="canShowComplaints" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Complaints</h4>
        <ul v-if="complaints.length" class="text-sm space-y-1">
          <li v-for="item in complaints" :key="item.id">• {{ item.complaint_text }}</li>
        </ul>
        <p v-else class="text-sm text-gray-500">No complaints recorded for this visit.</p>
      </section>

      <section v-if="canShowDiagnosis" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Diagnosis</h4>
        <ul v-if="diagnoses.length" class="text-sm space-y-1">
          <li v-for="item in diagnoses" :key="item.id">• {{ item.diagnosis_text }}</li>
        </ul>
        <p v-else class="text-sm text-gray-500">No diagnosis recorded for this visit.</p>
      </section>

      <section v-if="canShowClinicalScans" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Clinical Scans</h4>
        <div v-if="clinicalScans.length" class="space-y-3">
          <div
            v-for="scan in clinicalScans"
            :key="scan.id"
            class="rounded-lg border border-gray-100 dark:border-gray-700 p-3 text-sm"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-medium">{{ scan.scan_template_name || 'Clinical Scan' }}</p>
                <p class="text-gray-500">{{ scan.scan_date }} · {{ formatStatus(scan.status) }}</p>
              </div>
              <div class="flex gap-2">
                <BaseButton size="sm" variant="secondary" @click="viewScan(scan.id)">View</BaseButton>
                <BaseButton v-if="canPrintScan" size="sm" variant="ghost" :loading="scanPrintLoading === scan.id" @click="printScan(scan.id)">Print</BaseButton>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500">No clinical scans recorded for this visit.</p>
      </section>

      <section v-if="canShowLaboratoryResults" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Laboratory Results</h4>
        <div v-if="laboratoryResults.length" class="space-y-3">
          <div
            v-for="result in laboratoryResults"
            :key="result.id"
            class="rounded-lg border border-gray-100 dark:border-gray-700 p-3 text-sm"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-medium">{{ result.test_name || 'Laboratory Result' }}</p>
                <p class="text-gray-500">{{ result.result_date }} · {{ formatStatus(result.status) }}</p>
              </div>
              <div class="flex gap-2">
                <BaseButton size="sm" variant="secondary" @click="viewLaboratoryResult(result.id)">View</BaseButton>
                <BaseButton v-if="canPrintLaboratoryResult" size="sm" variant="ghost" :loading="laboratoryPrintLoading === result.id" @click="printLaboratoryResult(result.id)">Print Report</BaseButton>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500">No laboratory results recorded for this visit.</p>
      </section>

      <section v-if="canShowPrescription" class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="font-semibold mb-3">Prescription</h4>
        <template v-if="details.prescription">
          <p v-if="details.prescription.notes" class="text-sm mb-3">{{ details.prescription.notes }}</p>
          <div v-if="prescriptionMedicines.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500">
                  <th class="py-1 pr-3">Medicine</th>
                  <th class="py-1 pr-3">Dose Time</th>
                  <th class="py-1">Meal</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="med in prescriptionMedicines" :key="med.id" class="border-t border-gray-100 dark:border-gray-700">
                  <td class="py-2 pr-3">{{ [med.mdcn_type, med.mdcn_name, med.mdcn_size].filter(Boolean).join(' ') }}</td>
                  <td class="py-2 pr-3">{{ med.dose_time_text || '—' }}</td>
                  <td class="py-2">{{ med.dose_from_meal_text || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-sm text-gray-500">No medicines in this prescription.</p>
          <div v-if="canReprescribe || canReprint" class="flex flex-wrap gap-2 mt-4">
            <BaseButton v-if="canReprescribe" size="sm" @click="goReprescribe">Re-Prescribe</BaseButton>
            <BaseButton v-if="canReprint" size="sm" variant="secondary" :loading="reprintLoading" @click="openReprint">Reprint</BaseButton>
          </div>
        </template>
        <p v-else class="text-sm text-gray-500">No prescription created for this visit.</p>
      </section>
    </div>

    <div v-else class="text-sm text-gray-500 py-8 text-center">Select a visit to view details.</div>

    <PrescriptionPrintSettingsModal
      v-model="showPrintModal"
      :print-data="printData"
      title="Prescription Print Preview"
      :redirect-after-close="false"
    />

    <PrescriptionPrintSettingsModal
      v-model="showScanPrintModal"
      :print-data="scanPrintData"
      title="Clinical Scan Print Preview"
      :show-empty-clinical-scans-as-na="false"
      :redirect-after-close="false"
    />
    <LaboratoryResultPrintModal
      v-model="showLaboratoryPrintModal"
      :print-data="laboratoryPrintData"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { prescriptionService } from '@/services/prescriptionService';
import { clinicalScanService } from '@/services/clinicalScanService';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import LaboratoryResultPrintModal from '@/components/laboratory/LaboratoryResultPrintModal.vue';
import PrescriptionPrintSettingsModal from '@/components/prescription/PrescriptionPrintSettingsModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { displayPatientAge, formatGender } from '@/utils/formatters';

const props = defineProps({
  details: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const authStore = useAuthStore();
const router = useRouter();
const toastStore = useToastStore();
const showPrintModal = ref(false);
const printData = ref(null);
const reprintLoading = ref(false);
const showScanPrintModal = ref(false);
const scanPrintData = ref(null);
const scanPrintLoading = ref(null);
const showLaboratoryPrintModal = ref(false);
const laboratoryPrintData = ref(null);
const laboratoryPrintLoading = ref(null);

const canShowVitals = computed(() => authStore.can('view patient vitals'));
const canShowComplaints = computed(() => authStore.can('view visit complaints'));
const canShowDiagnosis = computed(() => authStore.can('view visit diagnosis'));
const canShowPrescription = computed(() => authStore.can('view prescriptions'));
const canShowClinicalScans = computed(() =>
  authStore.can('view clinical scans') || authStore.can('view patient clinical scan history')
);
const canPrintScan = computed(() => authStore.can('print clinical scans'));
const canShowLaboratoryResults = computed(() =>
  authStore.can('view laboratory results') || authStore.can('view patient laboratory history')
);
const canPrintLaboratoryResult = computed(() => authStore.can('print laboratory results'));

const canReprescribe = computed(() => props.details?.visit?.can_represcribe ?? false);
const canReprint = computed(() => props.details?.visit?.can_reprint ?? false);

const complaints = computed(() => {
  const data = props.details?.complaints;
  return Array.isArray(data) ? data : (data?.data ?? []);
});

const diagnoses = computed(() => {
  const data = props.details?.diagnoses;
  return Array.isArray(data) ? data : (data?.data ?? []);
});

const prescriptionMedicines = computed(() => {
  const fromPrescription = props.details?.prescription?.medicines;
  const direct = props.details?.prescription_medicines;
  const list = fromPrescription ?? direct;
  return Array.isArray(list) ? list : (list?.data ?? []);
});

const clinicalScans = computed(() => {
  const data = props.details?.clinical_scans;
  return Array.isArray(data) ? data : (data?.data ?? []);
});

const laboratoryResults = computed(() => {
  const data = props.details?.laboratory_results;
  return Array.isArray(data) ? data : (data?.data ?? []);
});

function formatStatus(status) {
  return status ? status.replace(/_/g, ' ') : '—';
}

function goReprescribe() {
  if (!props.details?.visit?.id) return;
  router.push(`/queue/${props.details.visit.id}`);
}

async function openReprint() {
  const prescriptionId = props.details?.visit?.prescription_id ?? props.details?.prescription?.id;
  if (!prescriptionId) return;

  reprintLoading.value = true;
  try {
    const { data } = await prescriptionService.getPrintData(prescriptionId);
    printData.value = data.print_data;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load prescription for printing.');
  } finally {
    reprintLoading.value = false;
  }
}

function viewScan(scanId) {
  router.push(`/clinical-scans/${scanId}`);
}

function viewLaboratoryResult(resultId) {
  router.push(`/laboratory-results/${resultId}`);
}

async function printScan(scanId) {
  scanPrintLoading.value = scanId;
  try {
    const { data } = await clinicalScanService.getPrintData(scanId);
    scanPrintData.value = data.print_data;
    showScanPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load scan for printing.');
  } finally {
    scanPrintLoading.value = null;
  }
}

async function printLaboratoryResult(resultId) {
  laboratoryPrintLoading.value = resultId;
  try {
    const visitId = props.details?.visit?.id ?? props.details?.id;
    const { data } = visitId
      ? await laboratoryResultService.getVisitPrintData(visitId)
      : await laboratoryResultService.getPrintData(resultId);
    laboratoryPrintData.value = data.print_data ?? data.data?.print_data ?? null;
    showLaboratoryPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load result for printing.');
  } finally {
    laboratoryPrintLoading.value = null;
  }
}
</script>
