<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit Laboratory Result</h2>

    <div v-if="pageLoading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <template v-else-if="result">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm mb-6">
        <h3 class="font-semibold mb-2">Patient &amp; Visit</h3>
        <p class="font-mono text-teal-600">{{ result.patient?.mr_number }}</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ result.patient?.patient_name }}</p>
        <p class="text-sm text-gray-500">
          {{ result.patient?.patient_father_name || '—' }} ·
          {{ formatGender(result.patient?.patient_gender) }} ·
          {{ displayPatientAge(result.patient) }}
        </p>
        <p class="text-sm text-gray-500 mt-2">
          Visit: {{ result.visit?.visit_date || 'Not Linked / No Visit' }}
          <span v-if="result.visit"> · Doctor: {{ result.visit?.doctor?.name || 'Unassigned' }}</span>
        </p>
        <p v-if="remainingDraftCount > 0" class="text-sm text-teal-700 dark:text-teal-300 mt-2">
          {{ remainingDraftCount }} more draft test(s) after this one.
        </p>
        <p class="text-sm text-gray-500 mt-1">
          Template: {{ result.test_name || result.template?.test_name || '—' }}
        </p>
      </div>

      <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-5" @submit.prevent="submit('completed')">
        <BaseInput
          v-model="form.test_price"
          type="number"
          min="0"
          step="0.01"
          label="Test Price"
          placeholder="Enter test price"
          :error="errors.test_price"
        />

        <LaboratoryXrayForm
          v-if="isImagingTest"
          v-model="resultValues"
          :result-id="result.id"
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
          <BaseButton type="submit" :loading="saving && savingStatus === 'completed'">Save Result</BaseButton>
          <BaseButton
            type="button"
            variant="secondary"
            :loading="saving && savingStatus === 'draft'"
            @click="submit('draft')"
          >
            Save as Draft
          </BaseButton>
          <BaseButton type="button" variant="secondary" @click="$router.push(`/laboratory-results/${result.id}`)">Cancel</BaseButton>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { useFormErrors } from '@/composables/useFormErrors';
import { fetchDraftTests, loadDraftResultForm } from '@/utils/laboratoryDraftQueue';
import { isImagingTestType, serializeResultValues } from '@/utils/laboratory';
import { displayPatientAge, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import LaboratoryDynamicFields from '@/components/laboratory/LaboratoryDynamicFields.vue';
import LaboratoryXrayForm from '@/components/laboratory/LaboratoryXrayForm.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const result = ref(null);
const resultValues = ref([]);
const currentTestType = ref('standard');
const draftQueue = ref([]);
const pageLoading = ref(true);
const saving = ref(false);
const savingStatus = ref('');

const remainingDraftCount = computed(() => {
  if (!result.value || !draftQueue.value.length) return 0;
  return draftQueue.value.filter((d) => d.id !== result.value.id).length;
});

const isImagingTest = computed(() => isImagingTestType(currentTestType.value));

const form = reactive({
  test_price: '',
  remarks: '',
});

async function submit(status) {
  clearErrors();
  saving.value = true;
  savingStatus.value = status;

  try {
    const payload = {
      status,
      test_price: form.test_price === '' ? null : Number(form.test_price),
      remarks: form.remarks?.trim() || null,
      values: serializeResultValues(resultValues.value),
    };

    await laboratoryResultService.updateResult(route.params.id, payload);
    toastStore.success(status === 'draft' ? 'Result saved as draft.' : 'Laboratory result updated.');

    if (status === 'completed') {
      const patientId = result.value.patient_id;
      const visitId = result.value.patient_visit_id ?? null;
      const drafts = await fetchDraftTests(patientId, visitId);
      const next = drafts.find((d) => d.id !== result.value.id) ?? drafts[0];

      if (next) {
        router.push(`/laboratory-results/${next.id}/edit`);
        return;
      }
    }

    router.push(`/laboratory-results/${route.params.id}`);
  } catch (e) {
    setErrors(e);
    const statusCode = e.response?.status;
    const message = statusCode === 403
      ? (e.response?.data?.message ?? 'You are not authorized to update this laboratory result.')
      : (e.response?.data?.message ?? 'Update failed.');
    toastStore.error(message);
  } finally {
    saving.value = false;
    savingStatus.value = '';
  }
}

onMounted(async () => {
  try {
    const loaded = await loadDraftResultForm(route.params.id);
    result.value = loaded.result;
    currentTestType.value = loaded.result.test_type || loaded.result.template?.test_type || 'standard';
    form.remarks = loaded.form.remarks;
    form.test_price = loaded.form.test_price;
    resultValues.value = loaded.resultValues;

    draftQueue.value = await fetchDraftTests(
      loaded.result.patient_id,
      loaded.result.patient_visit_id ?? null
    );
  } catch {
    toastStore.error('Failed to load laboratory result.');
    router.push('/laboratory-results');
  } finally {
    pageLoading.value = false;
  }
});
</script>
