<template>
  <div
    v-if="shouldShow"
    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm mt-6"
  >
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Laboratory History</h3>

    <div v-if="isLoading" class="h-20 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />

    <div v-else class="space-y-5">
      <section v-if="currentVisitResults.length">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Visit Results</h4>
        <div class="space-y-3">
          <article
            v-for="result in currentVisitResults"
            :key="`current-${result.id}`"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
          >
            <LaboratoryResultCard
              :result="result"
              :can-print="canPrint && isPrintableResult(result)"
              :printing="props.printingResultId === result.id"
              @print="emitPrint(result.id)"
            />
          </article>
        </div>
      </section>

      <section v-if="previousResults.length">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Previous Result History</h4>
        <div class="space-y-3">
          <article
            v-for="result in previousResults"
            :key="`previous-${result.id}`"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
          >
            <LaboratoryResultCard
              :result="result"
              :can-print="canPrint && isPrintableResult(result)"
              :printing="props.printingResultId === result.id"
              @print="emitPrint(result.id)"
            />
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import LaboratoryResultCard from '@/components/laboratory/LaboratoryResultCard.vue';
const props = defineProps({
  patientId: { type: [Number, String], required: true },
  currentVisitId: { type: [Number, String], default: null },
  laboratoryHistory: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  printingResultId: { type: [Number, String, null], default: null },
  hideWhenEmpty: { type: Boolean, default: true },
});

const emit = defineEmits(['print-result']);

const authStore = useAuthStore();
const toastStore = useToastStore();

const internalLoading = ref(false);
const history = ref({
  current_visit_results: [],
  previous_results: [],
});

const canPrint = computed(() => authStore.can('print laboratory results'));
const isLoading = computed(() => props.loading || internalLoading.value);
const currentVisitResults = computed(() => history.value.current_visit_results ?? []);
const previousResults = computed(() => history.value.previous_results ?? []);
const hasAnyResults = computed(() => currentVisitResults.value.length > 0 || previousResults.value.length > 0);
const shouldShow = computed(() => {
  if (!props.hideWhenEmpty) {
    return true;
  }

  return isLoading.value || hasAnyResults.value;
});

function applyHistory(payload) {
  history.value = {
    current_visit_results: payload?.current_visit_results ?? [],
    previous_results: payload?.previous_results ?? [],
  };
}

async function loadHistory() {
  if (!props.patientId) return;

  if (props.laboratoryHistory) {
    applyHistory(props.laboratoryHistory);
    return;
  }

  internalLoading.value = true;
  try {
    const { data } = await laboratoryResultService.getLaboratoryHistory(props.patientId, {
      current_visit_id: props.currentVisitId || undefined,
    });
    applyHistory(data);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load laboratory history.');
    applyHistory({ current_visit_results: [], previous_results: [] });
  } finally {
    internalLoading.value = false;
  }
}

function emitPrint(resultId) {
  emit('print-result', resultId);
}

function isPrintableResult(result) {
  return ['completed', 'verified'].includes(result?.status);
}

watch(
  () => [props.laboratoryHistory, props.patientId, props.currentVisitId],
  () => {
    if (props.laboratoryHistory) {
      applyHistory(props.laboratoryHistory);
      return;
    }

    loadHistory();
  },
  { deep: true }
);

onMounted(loadHistory);
</script>
