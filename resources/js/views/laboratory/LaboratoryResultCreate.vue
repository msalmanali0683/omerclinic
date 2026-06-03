<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Laboratory Result Entry</h2>

    <div class="space-y-6">
      <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">1. Find Patient Visit</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Search patient visits by MR number, name, cell, or CNIC.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <BaseInput
            v-model="patientSearch"
            placeholder="Search patient visit..."
            class="flex-1"
            @keyup.enter="searchPatients"
          />
          <BaseButton variant="secondary" :loading="searchLoading" @click="searchPatients">Search</BaseButton>
        </div>

        <div v-if="searchLoading" class="h-24 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />

        <div v-else-if="searchResults.length" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">MR#</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Patient</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Visit</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Doctor</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Status</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Select</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="item in searchResults"
                :key="item.visit.id"
                :class="selectedVisit?.id === item.visit.id ? 'bg-teal-50 dark:bg-teal-900/20' : ''"
              >
                <td class="px-3 py-2 font-mono text-teal-600">{{ item.patient?.mr_number }}</td>
                <td class="px-3 py-2">
                  <div class="font-medium">{{ item.patient?.patient_name }}</div>
                  <div class="text-xs text-gray-500">
                    {{ formatGender(item.patient?.patient_gender) }} · {{ displayPatientAge(item.patient) }}
                  </div>
                </td>
                <td class="px-3 py-2">{{ item.visit?.visit_date }} {{ item.visit?.visit_time || '' }}</td>
                <td class="px-3 py-2">{{ item.visit?.doctor?.name || 'Unassigned' }}</td>
                <td class="px-3 py-2 capitalize">{{ (item.visit?.status || '').replace(/_/g, ' ') }}</td>
                <td class="px-3 py-2">
                  <BaseButton
                    variant="ghost"
                    size="sm"
                    @click="selectVisit(item)"
                  >
                    {{ selectedVisit?.id === item.visit.id ? 'Selected' : 'Select' }}
                  </BaseButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else-if="searchedOnce" class="text-sm text-gray-500">No patient visits found.</p>
        <p v-if="errors.patient_id || errors.patient_visit_id" class="text-sm text-red-600">
          {{ errors.patient_id || errors.patient_visit_id }}
        </p>
      </section>

      <section
        v-if="selectedPatient"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm"
      >
        <h3 class="font-semibold mb-2">Selected Patient</h3>
        <p class="font-mono text-teal-600">{{ selectedPatient.mr_number }}</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedPatient.patient_name }}</p>
        <p class="text-sm text-gray-500">
          {{ selectedPatient.patient_father_name || '—' }} ·
          {{ formatGender(selectedPatient.patient_gender) }} ·
          {{ displayPatientAge(selectedPatient) }} ·
          {{ selectedPatient.patient_cell || '—' }}
        </p>
      </section>

      <section
        v-if="selectedVisit"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-5"
      >
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">2. Laboratory Details</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Choose a test template and complete the result fields.</p>
        </div>

        <BaseSelect
          v-model="form.laboratory_test_template_id"
          label="Laboratory Test Template"
          :options="templateOptions"
          placeholder="Select template"
          :error="errors.laboratory_test_template_id"
          required
          @update:model-value="onTemplateChange"
        />

        <div v-if="templateLoading" class="h-32 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />

        <template v-else-if="resultValues.length">
          <LaboratoryDynamicFields v-model="resultValues" :error="errors.values" />

          <div>
            <label class="block text-sm font-medium mb-1">Remarks</label>
            <textarea
              v-model="form.remarks"
              rows="2"
              placeholder="Optional remarks..."
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
            />
          </div>

          <div class="flex flex-wrap gap-3">
            <BaseButton :loading="saving && savingStatus === 'completed'" @click="submit('completed')">
              Save Result
            </BaseButton>
            <BaseButton variant="secondary" :loading="saving && savingStatus === 'draft'" @click="submit('draft')">
              Save as Draft
            </BaseButton>
            <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { useFormErrors } from '@/composables/useFormErrors';
import { buildResultValuesFromTemplate, serializeResultValues } from '@/utils/laboratory';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import LaboratoryDynamicFields from '@/components/laboratory/LaboratoryDynamicFields.vue';

const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const patientSearch = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchedOnce = ref(false);
const selectedPatient = ref(null);
const selectedVisit = ref(null);

const templateOptions = ref([]);
const templateLoading = ref(false);
const resultValues = ref([]);

const form = reactive({
  laboratory_test_template_id: '',
  remarks: '',
});

const saving = ref(false);
const savingStatus = ref('');

async function searchPatients() {
  searchLoading.value = true;
  searchedOnce.value = true;
  try {
    const { data } = await laboratoryResultService.searchPatientVisits({
      search: patientSearch.value || undefined,
    });
    searchResults.value = data.data ?? [];
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Patient search failed.');
  } finally {
    searchLoading.value = false;
  }
}

function selectVisit(item) {
  selectedPatient.value = item.patient;
  selectedVisit.value = item.visit;
  form.laboratory_test_template_id = '';
  resultValues.value = [];
}

async function loadTemplateOptions() {
  try {
    const { data } = await laboratoryTestTemplateService.getTemplateOptions();
    templateOptions.value = data.data ?? [];
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load templates.');
  }
}

async function onTemplateChange(templateId) {
  if (!templateId) {
    resultValues.value = [];
    return;
  }

  templateLoading.value = true;
  try {
    const { data } = await laboratoryTestTemplateService.getTemplate(templateId);
    const template = data.data ?? data;
    resultValues.value = buildResultValuesFromTemplate(template.fields ?? []);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load template fields.');
    resultValues.value = [];
  } finally {
    templateLoading.value = false;
  }
}

async function submit(status) {
  if (!selectedPatient.value || !selectedVisit.value) {
    toastStore.error('Please select a patient visit.');
    return;
  }

  if (!form.laboratory_test_template_id) {
    toastStore.error('Please select a laboratory template.');
    return;
  }

  clearErrors();
  saving.value = true;
  savingStatus.value = status;

  try {
    const payload = {
      patient_id: selectedPatient.value.id,
      patient_visit_id: selectedVisit.value.id,
      laboratory_test_template_id: Number(form.laboratory_test_template_id),
      status,
      remarks: form.remarks?.trim() || null,
      values: serializeResultValues(resultValues.value),
    };

    const { data } = await laboratoryResultService.createResult(payload);
    const result = data.data ?? data;
    toastStore.success(status === 'draft' ? 'Laboratory result saved as draft.' : 'Laboratory result saved.');
    router.push(`/laboratory-results/${result.id}`);
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
    savingStatus.value = '';
  }
}

onMounted(() => {
  loadTemplateOptions();
  searchPatients();
});
</script>
