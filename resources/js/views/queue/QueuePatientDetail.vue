<template>
  <div class="max-w-6xl">
    <div v-if="loading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <template v-else-if="visit">
      <div class="mb-6 flex items-start justify-between gap-4">
        <div>
          <p class="font-mono text-teal-600">{{ visit.patient?.mr_number }}</p>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ visit.patient?.patient_name }}</h2>
          <p class="text-gray-500 text-sm">
            {{ visit.patient?.patient_father_name }} · {{ formatGender(visit.patient?.patient_gender) }} · {{ displayPatientAge(visit.patient) }} · {{ visit.patient?.patient_cell }}
          </p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ visit.status.replace(/_/g, ' ') }}</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Personal Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Gender:</dt> {{ formatGender(visit.patient?.patient_gender) }}</div>
            <div><dt class="inline font-medium">Age:</dt> {{ displayPatientAge(visit.patient) }}</div>
            <div><dt class="inline font-medium">CNIC:</dt> {{ visit.patient?.patient_cnic || '—' }}</div>
            <div><dt class="inline font-medium">Address:</dt> {{ visit.patient?.patient_address || '—' }}</div>
          </dl>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Visit Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Date:</dt> {{ visit.visit_date }}</div>
            <div><dt class="inline font-medium">Time:</dt> {{ visit.visit_time || '—' }}</div>
            <div><dt class="inline font-medium">Doctor:</dt> {{ visit.doctor?.name || 'Unassigned' }}</div>
            <div><dt class="inline font-medium">Reason:</dt> {{ visit.reason_for_visit || '—' }}</div>
          </dl>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 mb-6">
        <BaseButton v-if="authStore.can('assign doctor to queue')" variant="secondary" @click="showAssign = true">Assign Doctor</BaseButton>
        <BaseButton v-if="authStore.can('cancel patient queue')" variant="ghost" @click="cancelVisit">Cancel</BaseButton>
      </div>

      <!-- Vitals + Clinical (complaints/diagnosis) -->
      <div
        v-if="authStore.can('view patient vitals') || authStore.can('view visit complaints') || authStore.can('view visit diagnosis')"
        class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6"
      >
        <div v-if="authStore.can('view patient vitals')" class="space-y-4">
          <CurrentVisitVitals
            v-if="hasSavedCurrentVitals"
            :vitals="currentVisitVitals"
            @edit="showEditVitalsModal = true"
          />
          <VitalsForm
            v-else-if="authStore.can('create patient vitals')"
            :patient-id="visit.patient_id"
            :visit-id="visit.id"
            @saved="handleVitalsCreated"
            @error="handleVitalsCreateError"
          />
          <EditVitalsModal
            v-if="showEditVitalsModal && currentVisitVitals"
            :show="showEditVitalsModal"
            :vitals="currentVisitVitals"
            :patient-id="visit.patient_id"
            :visit-id="visit.id"
            @saved="handleVitalsUpdated"
            @error="handleVitalsUpdateError"
            @close="showEditVitalsModal = false"
          />
          <VitalsHistory
            v-if="authStore.can('view previous patient vitals') && vitalsHistory.length"
            :items="vitalsHistory"
            :mr-number="visit.patient?.mr_number"
          />
          <ClinicalScanHistory
            v-if="showClinicalScanHistory"
            :patient-id="visit.patient_id"
            :current-visit-id="visit.id"
            :clinical-scan-history="clinicalScanHistory"
            :printing-scan-id="scanPrintLoading"
            @print-scan="openScanPrintPreview"
          />
          <LaboratoryHistory
            v-if="showLaboratoryHistory"
            :patient-id="visit.patient_id"
            :current-visit-id="visit.id"
            :laboratory-history="laboratoryHistory"
            :printing-result-id="laboratoryPrintLoading"
            @print-result="openLaboratoryPrintPreview"
          />
        </div>

        <div class="space-y-6">
          <div
            v-if="authStore.can('view visit complaints')"
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm space-y-4"
          >
            <h3 class="font-semibold text-lg">Complaints</h3>
            <ComplaintSelector
              v-if="canAddComplaints"
              :patient-id="visit.patient_id"
              :visit-id="visit.id"
              @complaint-added="onComplaintAdded"
            />
            <VisitComplaintsTable
              :items="visitComplaints"
              :can-edit="authStore.can('edit visit complaints')"
              :can-delete="authStore.can('delete visit complaints')"
              @complaint-updated="onComplaintUpdated"
              @complaint-deleted="onComplaintDeleted"
            />
          </div>

          <div
            v-if="authStore.can('view visit diagnosis')"
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm space-y-4"
          >
            <h3 class="font-semibold text-lg">Diagnosis</h3>
            <DiagnosisSelector
              v-if="canAddDiagnosis"
              :patient-id="visit.patient_id"
              :visit-id="visit.id"
              @diagnosis-added="onDiagnosisAdded"
            />
            <VisitDiagnosisTable
              :items="visitDiagnoses"
              :can-edit="authStore.can('edit visit diagnosis')"
              :can-delete="authStore.can('delete visit diagnosis')"
              @diagnosis-updated="onDiagnosisUpdated"
              @diagnosis-deleted="onDiagnosisDeleted"
            />
          </div>
        </div>
      </div>

      <!-- Prescription -->
      <div
        v-if="canShowPrescriptionForm"
        class="rounded-2xl border border-emerald-200 bg-white shadow-md dark:border-emerald-900/50 dark:bg-gray-800"
      >
        <div class="bg-gradient-to-r from-violet-600 via-emerald-600 to-teal-600 px-5 py-4 text-white">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                <AppIcon name="prescription" class-name="w-6 h-6 text-white" />
              </span>
              <div>
                <h3 class="text-lg font-bold leading-tight">
                  {{ prescriptionMode === 'edit' ? 'Re-Prescribe' : 'Create Prescription' }}
                </h3>
                <p class="text-sm text-white/85">Add medicines, follow-up visit, then save and print</p>
              </div>
            </div>
            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
              {{ prescriptionMode === 'edit' ? 'Update mode' : 'New prescription' }}
            </span>
          </div>
        </div>

        <form class="space-y-5 p-5 sm:p-6" novalidate @submit.prevent="savePrescription">
          <PrescriptionMedicineRows
            v-if="authStore.can('select medicines in prescription')"
            v-model="prescriptionMedicineRows"
            :dose-time-options="doseTimeOptions"
            :dose-from-meal-options="doseFromMealOptions"
            :errors="rxErrors"
          />

          <div class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-violet-50 p-4 dark:border-indigo-800 dark:from-indigo-950/40 dark:to-violet-950/30">
            <div class="mb-3 flex items-center gap-2">
              <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                <AppIcon name="calendar" class-name="w-4 h-4" />
              </span>
              <div>
                <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">Follow-up Visit</p>
                <p class="text-xs text-indigo-600/80 dark:text-indigo-300/80">Shown on printed prescription footer</p>
              </div>
            </div>
            <BaseSelect
              v-model="rx.next_visit_days"
              label="Next Visit After"
              :options="nextVisitDayOptions"
              :error="rxErrors.next_visit_days"
            />
          </div>

          <p v-if="rxErrors.medicines" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
            {{ rxErrors.medicines }}
          </p>

          <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
            <BaseButton type="submit" class="min-w-[180px]" :loading="rxSaving" :disabled="rxSaving">
              {{ prescriptionMode === 'edit' ? 'Update Prescription' : 'Save Prescription' }}
            </BaseButton>
          </div>
        </form>
      </div>

      <div v-if="previousVisits.length" class="mt-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
        <h3 class="font-semibold mb-2">Previous Visits</h3>
        <ul class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
          <li v-for="v in previousVisits" :key="v.id">{{ v.visit_date }} — {{ v.status.replace(/_/g, ' ') }}</li>
        </ul>
      </div>
    </template>

    <BaseModal v-model="showAssign" title="Assign Doctor">
      <BaseSelect v-model="assignDoctorId" label="Doctor" :options="doctorOptions" placeholder="Select doctor" />
      <template #footer>
        <BaseButton variant="secondary" @click="showAssign = false">Cancel</BaseButton>
        <BaseButton :loading="assigning" @click="assignDoctor">Assign</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { patientQueueService } from '@/services/patientQueueService';
import { prescriptionService } from '@/services/prescriptionService';
import { clinicalScanService } from '@/services/clinicalScanService';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { userService } from '@/services/userService';
import CurrentVisitVitals from '@/components/vitals/CurrentVisitVitals.vue';
import VitalsForm from '@/components/vitals/VitalsForm.vue';
import EditVitalsModal from '@/components/vitals/EditVitalsModal.vue';
import VitalsHistory from '@/components/vitals/VitalsHistory.vue';
import ClinicalScanHistory from '@/components/clinical-scans/ClinicalScanHistory.vue';
import LaboratoryHistory from '@/components/laboratory/LaboratoryHistory.vue';
import PrescriptionMedicineRows from '@/components/prescription/PrescriptionMedicineRows.vue';
import { mapPrescriptionMedicineToRow, persistNewMedicineRows, preparePrescriptionMedicineRowsForSave, serializePrescriptionMedicineRows, stripEmptyPrescriptionMedicineRows, appendDiagnosisTemplateMedicines } from '@/utils/prescriptionMedicines';
import { diagnosisMedicineTemplateService } from '@/services/diagnosisMedicineTemplateService';
import { medicineDoseTimeService } from '@/services/medicineDoseTimeService';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import ComplaintSelector from '@/components/complaints/ComplaintSelector.vue';
import VisitComplaintsTable from '@/components/complaints/VisitComplaintsTable.vue';
import DiagnosisSelector from '@/components/diagnosis/DiagnosisSelector.vue';
import VisitDiagnosisTable from '@/components/diagnosis/VisitDiagnosisTable.vue';
import { directPrintClinicalScan, directPrintLaboratoryReport, directPrintPrescription } from '@/utils/directPrint';
import { NEXT_VISIT_DAY_OPTIONS } from '@/utils/prescriptionPrintSettings';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import AppIcon from '@/components/ui/AppIcon.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseModal from '@/components/ui/BaseModal.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const visit = ref(null);
const currentVisitVitals = ref(null);
const showEditVitalsModal = ref(false);
const vitalsHistory = ref([]);
const clinicalScanHistory = ref(null);
const laboratoryHistory = ref(null);
const visitComplaints = ref([]);
const visitDiagnoses = ref([]);
const previousVisits = ref([]);
const loading = ref(true);
const showAssign = ref(false);
const assignDoctorId = ref('');
const assigning = ref(false);
const doctorOptions = ref([]);
const existingPrescription = ref(null);
const prescriptionMedicineRows = ref([]);
const doseTimeOptions = ref([]);
const doseFromMealOptions = ref([]);
const rx = reactive({ next_visit_days: '' });
const nextVisitDayOptions = NEXT_VISIT_DAY_OPTIONS;
const rxErrors = reactive({});
const rxSaving = ref(false);
const printData = ref(null);
const scanPrintLoading = ref(false);
const laboratoryPrintLoading = ref(false);
const redirectAfterPrint = ref(false);
const loadedDiagnosisTemplateIds = ref(new Set());

const printRedirectTo = computed(() => (
  authStore.hasRole('doctor') ? { name: 'queue.doctor' } : { name: 'queue.index' }
));

const canAddComplaints = computed(() =>
  authStore.can('create visit complaints') || authStore.can('add complaints during prescription')
);
const canAddDiagnosis = computed(() =>
  authStore.can('create visit diagnosis') || authStore.can('add diagnosis during prescription')
);

const canViewClinicalScans = computed(() =>
  authStore.can('view clinical scans') || authStore.can('view patient clinical scan history')
);
const canViewLaboratoryResults = computed(() =>
  authStore.can('view laboratory results') || authStore.can('view patient laboratory history')
);

function hasClinicalScanHistoryData(history) {
  return Boolean(history?.current_visit_scans?.length || history?.previous_scans?.length);
}

function hasLaboratoryHistoryData(history) {
  return Boolean(history?.current_visit_results?.length || history?.previous_results?.length);
}

const showClinicalScanHistory = computed(() =>
  canViewClinicalScans.value && hasClinicalScanHistoryData(clinicalScanHistory.value)
);
const showLaboratoryHistory = computed(() =>
  canViewLaboratoryResults.value && hasLaboratoryHistoryData(laboratoryHistory.value)
);

const canUseDiagnosisMedicineTemplates = computed(() =>
  authStore.can('use diagnosis medicine templates in prescription')
);

const hasSavedCurrentVitals = computed(() => Boolean(currentVisitVitals.value?.id));

const prescriptionMode = computed(() => (
  visit.value?.has_prescription || existingPrescription.value?.id ? 'edit' : 'create'
));

const canShowPrescriptionForm = computed(() => {
  if (!visit.value || visit.value.status === 'cancelled') {
    return false;
  }

  if (prescriptionMode.value === 'edit') {
    return visit.value.can_represcribe
      || authStore.can('edit prescription')
      || authStore.can('update prescription')
      || authStore.can('re-prescribe prescription');
  }

  return authStore.can('create prescription')
    && ['pending_prescription', 'in_consultation'].includes(visit.value.status);
});

function resetPrescriptionForm() {
  existingPrescription.value = null;
  prescriptionMedicineRows.value = [];
  rx.next_visit_days = '';
}

function normalizeNextVisitDays(value) {
  if (value === '' || value === null || value === undefined) {
    return null;
  }

  return Number(value);
}

function applyPrescriptionToForm(prescription) {
  existingPrescription.value = prescription;
  rx.next_visit_days = prescription?.next_visit_days ?? '';

  const items = prescription?.medicines ?? [];
  prescriptionMedicineRows.value = items.length
    ? items.map(mapPrescriptionMedicineToRow)
    : [];
}

async function loadExistingPrescription(prescriptionId = null) {
  try {
    if (prescriptionId) {
      const { data } = await prescriptionService.getPrescription(prescriptionId);
      applyPrescriptionToForm(data.data ?? data);
      return;
    }

    const { data } = await prescriptionService.getPrescriptionByVisit(visit.value.id);
    applyPrescriptionToForm(data.prescription ?? data.data);
    if (data.dose_time_options?.length) {
      doseTimeOptions.value = data.dose_time_options;
    }
    if (data.dose_from_meal_options?.length) {
      doseFromMealOptions.value = data.dose_from_meal_options;
    }
  } catch {
    toastStore.error('Failed to load existing prescription.');
  }
}

async function loadPrescriptionPrintData(data) {
  if (data.print_data) {
    printData.value = data.print_data;
    return;
  }

  if (data.can_print === false) {
    printData.value = null;
    return;
  }

  const prescriptionId = data.data?.id ?? data.prescription?.id;
  if (!prescriptionId) {
    printData.value = null;
    return;
  }

  try {
    const printResponse = await prescriptionService.getPrintData(prescriptionId);
    printData.value = printResponse.data.print_data;
  } catch (e) {
    toastStore.warning(e.response?.data?.message || 'Prescription saved, but print is not available.');
    printData.value = null;
  }
}

async function handleSaveSuccess(data, redirect = true) {
  toastStore.success(data.message || (prescriptionMode.value === 'edit'
    ? 'Prescription updated successfully.'
    : 'Prescription saved successfully.'));

  await loadPrescriptionPrintData(data);

  if (data.data?.id || data.prescription?.id) {
    applyPrescriptionToForm(data.data ?? data.prescription);
  } else {
    prescriptionMedicineRows.value = preparePrescriptionMedicineRowsForSave(prescriptionMedicineRows.value);
  }

  if (printData.value?.visit) {
    visit.value = {
      ...visit.value,
      ...printData.value.visit,
      ...prescriptionVisitMetaFromVisit(printData.value.visit),
      status: printData.value.visit.status || visit.value.status,
    };
  } else if (prescriptionMode.value === 'create') {
    visit.value = {
      ...visit.value,
      status: 'prescribed',
      has_prescription: true,
      prescription_id: data.data?.id ?? data.prescription_id ?? existingPrescription.value?.id,
      can_represcribe: true,
      can_reprint: data.can_print !== false,
      can_update_prescription: true,
    };
  }

  if (printData.value) {
    redirectAfterPrint.value = redirect;
    try {
      await directPrintPrescription(printData.value, {
        onAfterPrint: () => {
          if (redirectAfterPrint.value) {
            router.push(printRedirectTo.value);
          }
        },
      });
    } catch (e) {
      toastStore.error(e.message ?? 'Unable to print prescription.');
    }
  } else if (data.can_print === false) {
    toastStore.warning('Prescription saved, but print is not available for this user.');
  }
}

function prescriptionVisitMetaFromVisit(visitData) {
  return {
    has_prescription: visitData.has_prescription ?? true,
    prescription_id: visitData.prescription_id ?? existingPrescription.value?.id ?? null,
    can_represcribe: visitData.can_represcribe ?? true,
    can_reprint: visitData.can_reprint ?? true,
    can_update_prescription: visitData.can_update_prescription ?? true,
  };
}

function normalizeList(payload) {
  if (Array.isArray(payload)) return payload;
  return payload?.data ?? [];
}

function prescriptionDiagnosisSummary() {
  return visitDiagnoses.value.map((d) => d.diagnosis_text).filter(Boolean).join(', ');
}

function flattenValidationErrors(errors = {}) {
  const flat = {};
  Object.entries(errors).forEach(([key, value]) => {
    flat[key] = Array.isArray(value) ? value[0] : value;
  });
  return flat;
}

async function openScanPrintPreview(scanId) {
  scanPrintLoading.value = scanId;
  try {
    const { data } = await clinicalScanService.getPrintData(scanId);
    await directPrintClinicalScan(data.print_data ?? null);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load scan for printing.');
  } finally {
    scanPrintLoading.value = false;
  }
}

async function openLaboratoryPrintPreview(resultId) {
  laboratoryPrintLoading.value = resultId;
  try {
    const { data } = await laboratoryResultService.getPrintData(resultId);
    await directPrintLaboratoryReport(data.print_data ?? null);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load result for printing.');
  } finally {
    laboratoryPrintLoading.value = false;
  }
}

async function loadPrescriptionOptions() {
  if (!visit.value?.id) return;
  if (!canShowPrescriptionForm.value && !visit.value?.has_prescription) return;
  try {
    const { data } = await prescriptionService.getPrescriptionCreateData(visit.value.id);
    doseTimeOptions.value = data.dose_time_options ?? [];
    doseFromMealOptions.value = data.dose_from_meal_options ?? [];
    if (data.clinical_scan_history) {
      clinicalScanHistory.value = data.clinical_scan_history;
    }
  } catch {
    try {
      const [times, meals] = await Promise.all([
        medicineDoseTimeService.getDoseTimeOptions(),
        medicineDoseFromMealService.getDoseFromMealOptions(),
      ]);
      doseTimeOptions.value = times.data?.data ?? [];
      doseFromMealOptions.value = meals.data?.data ?? [];
    } catch { /* optional */ }
  }
}

async function refreshClinicalScanHistory() {
  if (!visit.value?.patient_id || !canViewClinicalScans.value) {
    return;
  }

  try {
    const { data } = await clinicalScanService.getClinicalScanHistory(visit.value.patient_id, {
      current_visit_id: visit.value.id,
    });
    clinicalScanHistory.value = data;
  } catch {
    // Ignore background refresh errors.
  }
}

async function load() {
  loading.value = true;
  try {
    const { data } = await patientQueueService.getQueueItem(route.params.id);
    visit.value = data.visit;
    currentVisitVitals.value = data.latest_vitals ?? null;
    vitalsHistory.value = normalizeList(data.vitals_history);
    clinicalScanHistory.value = data.clinical_scan_history ?? null;
    laboratoryHistory.value = data.laboratory_history ?? null;
    visitComplaints.value = normalizeList(data.visit_complaints);
    visitDiagnoses.value = normalizeList(data.visit_diagnoses);
    previousVisits.value = data.previous_visits ?? [];
    loadedDiagnosisTemplateIds.value = new Set();

    if (data.visit?.has_prescription) {
      await loadExistingPrescription(data.visit.prescription_id);
    } else {
      resetPrescriptionForm();
    }

    await loadPrescriptionOptions();

    if (canShowPrescriptionForm.value && prescriptionMode.value === 'create') {
      await loadMedicinesForVisitDiagnoses();
    }
  } catch {
    toastStore.error('Failed to load visit.');
    router.push('/queue');
  } finally {
    loading.value = false;
  }
}

function handleVitalsCreated(vital) {
  if (!vital?.id) return;
  currentVisitVitals.value = vital;
  toastStore.success('Vitals saved successfully.');
}

function handleVitalsUpdated(vital) {
  if (!vital?.id) return;
  currentVisitVitals.value = vital;
  showEditVitalsModal.value = false;
  toastStore.success('Vitals updated successfully.');
}

function handleVitalsCreateError(e) {
  toastStore.error(e?.response?.data?.message ?? 'Unable to save vitals.');
}

function handleVitalsUpdateError(e) {
  toastStore.error(e?.response?.data?.message ?? 'Unable to update vitals.');
}

function onComplaintAdded(complaint) {
  if (!complaint?.id) return;
  const exists = visitComplaints.value.some((item) => item.id === complaint.id);
  if (!exists) {
    visitComplaints.value.unshift(complaint);
  }
}

function onComplaintUpdated(complaint) {
  if (!complaint?.id) return;
  const index = visitComplaints.value.findIndex((item) => item.id === complaint.id);
  if (index !== -1) {
    visitComplaints.value[index] = complaint;
  }
}

function onComplaintDeleted(complaintId) {
  visitComplaints.value = visitComplaints.value.filter((item) => item.id !== complaintId);
}

async function onDiagnosisAdded(diagnosis) {
  if (!diagnosis?.id) return;
  const exists = visitDiagnoses.value.some((item) => item.id === diagnosis.id);
  if (!exists) {
    visitDiagnoses.value.unshift(diagnosis);
  }

  if (
    canShowPrescriptionForm.value
    && canUseDiagnosisMedicineTemplates.value
    && diagnosis.diagnosis_master_id
  ) {
    await loadMedicinesForDiagnosisMasterId(diagnosis.diagnosis_master_id);
  }
}

function onDiagnosisUpdated(diagnosis) {
  if (!diagnosis?.id) return;
  const index = visitDiagnoses.value.findIndex((item) => item.id === diagnosis.id);
  if (index !== -1) {
    visitDiagnoses.value[index] = diagnosis;
  }
}

function onDiagnosisDeleted(diagnosisId) {
  visitDiagnoses.value = visitDiagnoses.value.filter((item) => item.id !== diagnosisId);
}

function applyLoadedTemplateMedicines(templates, { silent = false } = {}) {
  if (!templates.length) {
    if (!silent) {
      toastStore.error('No medicines are mapped with this diagnosis.');
    }
    return;
  }

  const { rows, added, skipped } = appendDiagnosisTemplateMedicines(prescriptionMedicineRows.value, templates);
  prescriptionMedicineRows.value = rows;

  if (silent) {
    return;
  }

  if (added === 0 && skipped > 0) {
    toastStore.info('All medicines from this diagnosis are already added.');
    return;
  }

  if (skipped > 0) {
    toastStore.info('Some medicines were already added and were skipped.');
    return;
  }

  toastStore.success('Medicines loaded from diagnosis template.');
}

async function loadMedicinesForDiagnosisMasterId(diagnosisMasterId, { silent = false } = {}) {
  const id = Number(diagnosisMasterId);
  if (!id || !canUseDiagnosisMedicineTemplates.value || loadedDiagnosisTemplateIds.value.has(id)) {
    return;
  }

  try {
    const { data } = await diagnosisMedicineTemplateService.getTemplatesByDiagnosis(id);
    loadedDiagnosisTemplateIds.value.add(id);
    applyLoadedTemplateMedicines(data.medicines ?? data.data?.medicines ?? [], { silent });
  } catch (error) {
    if (!silent) {
      toastStore.error(error.response?.data?.message ?? 'Failed to load diagnosis medicines.');
    }
  }
}

async function loadMedicinesForVisitDiagnoses() {
  const diagnosisIds = [...new Set(
    visitDiagnoses.value
      .map((item) => item.diagnosis_master_id)
      .filter(Boolean)
  )];

  for (const diagnosisId of diagnosisIds) {
    await loadMedicinesForDiagnosisMasterId(diagnosisId, { silent: true });
  }
}

async function assignDoctor() {
  assigning.value = true;
  try {
    const { data } = await patientQueueService.assignDoctor(visit.value.id, { doctor_id: assignDoctorId.value });
    visit.value = data.visit;
    showAssign.value = false;
    toastStore.success(data.message);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to assign doctor.');
  } finally {
    assigning.value = false;
  }
}

async function cancelVisit() {
  if (!confirm('Cancel this queue entry?')) return;
  try {
    await patientQueueService.cancelQueue(visit.value.id);
    toastStore.success('Cancelled.');
    router.push('/queue');
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed.');
  }
}

async function savePrescription() {
  Object.keys(rxErrors).forEach((key) => delete rxErrors[key]);

  prescriptionMedicineRows.value = stripEmptyPrescriptionMedicineRows(prescriptionMedicineRows.value);

  try {
    prescriptionMedicineRows.value = await persistNewMedicineRows(prescriptionMedicineRows.value);
  } catch (error) {
    // Backend PrescriptionService also find-or-creates on save; do not block prescription.
    if (error.response?.status !== 405 && error.response?.status !== 404) {
      toastStore.warning(
        error.response?.data?.message
          ?? 'Could not save new medicine to master list before prescription; saving prescription anyway.'
      );
    }
  }

  const medicines = serializePrescriptionMedicineRows(prescriptionMedicineRows.value);
  if (!medicines.length) {
    rxErrors.medicines = 'Add at least one medicine with a name.';
    toastStore.error(rxErrors.medicines);
    return;
  }

  rxSaving.value = true;
  try {
    const payload = {
      notes: null,
      next_visit_days: normalizeNextVisitDays(rx.next_visit_days),
      medicines,
    };

    if (prescriptionMode.value === 'edit' && existingPrescription.value?.id) {
      const { data } = await prescriptionService.updatePrescription(existingPrescription.value.id, payload);
      await handleSaveSuccess(data);
      return;
    }

    const { data } = await prescriptionService.createPrescription({
      patient_id: visit.value.patient_id,
      patient_visit_id: visit.value.id,
      ...payload,
    });

    await handleSaveSuccess(data);
  } catch (e) {
    if (e.response?.status === 409 && e.response?.data?.prescription_id) {
      toastStore.warning(e.response.data.message || 'Prescription already exists for this visit.');
      await loadExistingPrescription(e.response.data.prescription_id);
      return;
    }

    const errs = e.response?.data?.errors ?? {};
    Object.assign(rxErrors, flattenValidationErrors(errs));
    if (errs.medicines) {
      rxErrors.medicines = Array.isArray(errs.medicines) ? errs.medicines[0] : errs.medicines;
    }
    toastStore.error(e.response?.data?.message ?? 'Failed to save prescription.');
  } finally {
    rxSaving.value = false;
  }
}

onMounted(async () => {
  await load();
  if (authStore.can('assign doctor to queue')) {
    try {
      const { data } = await userService.listDoctors();
      doctorOptions.value = (data.data ?? []).map((u) => ({ value: u.id, label: u.name }));
    } catch { /* optional */ }
  }
});

useAutoRefresh(() => refreshClinicalScanHistory(), 10000);
</script>
