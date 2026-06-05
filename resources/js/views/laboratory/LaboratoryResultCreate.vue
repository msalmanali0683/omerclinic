<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Laboratory Result Entry</h2>

    <div
      v-if="!routeResultId"
      class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm text-center space-y-4"
    >
      <p class="text-gray-600 dark:text-gray-400">
        Open a draft test from <strong>Laboratory Results</strong> to enter values here.
      </p>
      <BaseButton @click="$router.push('/laboratory-results')">Go to Laboratory Results</BaseButton>
    </div>

    <div v-else-if="pageLoading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <div v-else-if="loadError" class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-5 text-red-700 dark:text-red-300">
      {{ loadError }}
      <div class="mt-3">
        <BaseButton variant="secondary" @click="$router.push('/laboratory-results')">Back to Laboratory Results</BaseButton>
      </div>
    </div>

    <div v-else-if="selectedPatient" class="space-y-6">
      <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h3 class="font-semibold mb-2">Patient</h3>
        <p class="font-mono text-teal-600">{{ selectedPatient.mr_number }}</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedPatient.patient_name }}</p>
        <p v-if="selectedVisit" class="text-sm text-gray-500 mt-2">
          Visit: {{ selectedVisit.visit_date }} {{ selectedVisit.visit_time || '' }}
          <span v-if="selectedVisit.status" class="capitalize"> · {{ (selectedVisit.status || '').replace(/_/g, ' ') }}</span>
        </p>
        <p v-else class="text-sm text-amber-700 dark:text-amber-300 mt-2">No visit — report linked to patient only.</p>
      </section>

      <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-5">
        <div>
          <h3 class="font-semibold text-gray-900 dark:text-white">Laboratory Details</h3>
          <p v-if="draftQueue.length && editingResultId" class="text-sm text-teal-700 dark:text-teal-300 mt-1">
            Draft test {{ currentDraftIndex }} of {{ draftQueue.length }}:
            <strong>{{ currentDraftLabel }}</strong>
          </p>
        </div>

        <div class="rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20 p-3 text-sm">
          <strong>Test:</strong> {{ currentDraftLabel }}
          <span v-if="draftQueue.length > 1" class="text-gray-500 ml-2">
            ({{ draftQueue.length - 1 }} more draft after this)
          </span>
        </div>

        <BaseInput
          v-model="form.test_price"
          type="number"
          min="0"
          step="0.01"
          label="Test Price"
          placeholder="Enter test price"
          :error="errors.test_price"
        />

        <div v-if="templateLoading" class="h-32 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />

        <template v-else-if="resultValues.length">
          <LaboratoryXrayForm
            v-if="isImagingTest"
            v-model="resultValues"
            :result-id="editingResultId"
            :test-type="currentTestType"
            :error="errors.values"
          />
          <LaboratoryDynamicFields
            v-else
            v-model="resultValues"
            :error="errors.values"
          />

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
            <BaseButton variant="secondary" @click="$router.push('/laboratory-results')">Back</BaseButton>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { useFormErrors } from '@/composables/useFormErrors';
import { isImagingTestType, serializeResultValues } from '@/utils/laboratory';
import { fetchDraftTests, loadDraftResultForm } from '@/utils/laboratoryDraftQueue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import LaboratoryDynamicFields from '@/components/laboratory/LaboratoryDynamicFields.vue';
import LaboratoryXrayForm from '@/components/laboratory/LaboratoryXrayForm.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const routeResultId = computed(() => route.query.result_id || null);

const pageLoading = ref(false);
const loadError = ref('');
const selectedPatient = ref(null);
const selectedVisit = ref(null);
const draftQueue = ref([]);
const editingResultId = ref(null);
const currentDraftLabel = ref('');

const templateLoading = ref(false);
const resultValues = ref([]);
const currentTestType = ref('standard');

const form = reactive({
  test_price: '',
  remarks: '',
});

const saving = ref(false);
const savingStatus = ref('');

const currentDraftIndex = computed(() => {
  if (!editingResultId.value || !draftQueue.value.length) return 0;
  const index = draftQueue.value.findIndex((d) => d.id === editingResultId.value);
  return index >= 0 ? index + 1 : 1;
});

const isImagingTest = computed(() => isImagingTestType(currentTestType.value));

async function refreshDraftQueue() {
  if (!selectedPatient.value) {
    draftQueue.value = [];
    return;
  }

  draftQueue.value = await fetchDraftTests(
    selectedPatient.value.id,
    selectedVisit.value?.id ?? null
  );
}

async function openDraftResult(resultId) {
  templateLoading.value = true;
  try {
    const loaded = await loadDraftResultForm(resultId);
    editingResultId.value = loaded.result.id;
    currentDraftLabel.value = loaded.result.test_name || loaded.result.template?.test_name || 'Laboratory Test';
    currentTestType.value = loaded.result.test_type || loaded.result.template?.test_type || 'standard';
    form.test_price = loaded.form.test_price;
    form.remarks = loaded.form.remarks;
    resultValues.value = loaded.resultValues;
  } catch {
    throw new Error('Failed to load draft test.');
  } finally {
    templateLoading.value = false;
  }
}

async function loadFromResultId(resultId) {
  pageLoading.value = true;
  loadError.value = '';
  try {
    const { data } = await laboratoryResultService.getResult(resultId);
    const row = data.data ?? data;

    if (row.status !== 'draft') {
      loadError.value = 'This test is not a draft. Use Edit from Laboratory Results.';
      return;
    }

    selectedPatient.value = row.patient;
    selectedVisit.value = row.visit ?? null;
    await refreshDraftQueue();
    await openDraftResult(resultId);
  } catch {
    loadError.value = 'Could not load this laboratory result.';
  } finally {
    pageLoading.value = false;
  }
}

async function submit(status) {
  if (!editingResultId.value) {
    toastStore.error('No draft test loaded.');
    return;
  }

  clearErrors();
  saving.value = true;
  savingStatus.value = status;

  try {
    const payload = {
      test_price: form.test_price === '' ? 0 : Number(form.test_price),
      status,
      remarks: form.remarks?.trim() || null,
      values: serializeResultValues(resultValues.value),
    };

    await laboratoryResultService.updateResult(editingResultId.value, payload);

    if (status === 'completed') {
      await refreshDraftQueue();
      if (draftQueue.value.length) {
        toastStore.success(`Saved. Loading next draft test (${draftQueue.value.length} remaining).`);
        router.replace({ path: '/laboratory-results/create', query: { result_id: draftQueue.value[0].id } });
        await loadFromResultId(draftQueue.value[0].id);
      } else {
        toastStore.success('All draft tests completed.');
        router.push('/laboratory-results');
      }
    } else {
      toastStore.success('Draft saved.');
    }
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
    savingStatus.value = '';
  }
}

onMounted(async () => {
  if (routeResultId.value) {
    await loadFromResultId(routeResultId.value);
  }
});
</script>
