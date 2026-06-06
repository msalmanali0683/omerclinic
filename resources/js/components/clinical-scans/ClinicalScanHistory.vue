<template>
  <div
    v-if="shouldShow"
    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm mt-6"
  >
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Clinical Scan History</h3>

    <div v-if="isLoading" class="h-20 bg-gray-100 dark:bg-gray-700 rounded-lg animate-pulse" />

    <div v-else class="space-y-5">
      <section v-if="currentVisitScans.length">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Visit Scans</h4>
        <div class="space-y-3">
          <article
            v-for="scan in currentVisitScans"
            :key="`current-${scan.id}`"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
          >
            <ScanHistoryCard
              :scan="scan"
              :can-print="canPrint"
              :printing="props.printingScanId === scan.id"
              @print="emitPrint(scan.id)"
            />
          </article>
        </div>
      </section>

      <section v-if="previousScans.length">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Previous Scan History</h4>
        <div class="space-y-3">
          <article
            v-for="scan in previousScans"
            :key="`previous-${scan.id}`"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
          >
            <ScanHistoryCard
              :scan="scan"
              :can-print="canPrint"
              :printing="props.printingScanId === scan.id"
              @print="emitPrint(scan.id)"
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
import { clinicalScanService } from '@/services/clinicalScanService';
import ScanHistoryCard from '@/components/clinical-scans/ScanHistoryCard.vue';

const props = defineProps({
  patientId: { type: [Number, String], required: true },
  currentVisitId: { type: [Number, String], default: null },
  clinicalScanHistory: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  printingScanId: { type: [Number, String, null], default: null },
  hideWhenEmpty: { type: Boolean, default: true },
});

const emit = defineEmits(['print-scan']);

const authStore = useAuthStore();
const toastStore = useToastStore();

const internalLoading = ref(false);
const history = ref({
  current_visit_scans: [],
  previous_scans: [],
});

const canPrint = computed(() => authStore.can('print clinical scans'));
const isLoading = computed(() => props.loading || internalLoading.value);
const currentVisitScans = computed(() => history.value.current_visit_scans ?? []);
const previousScans = computed(() => history.value.previous_scans ?? []);
const hasAnyScans = computed(() => currentVisitScans.value.length > 0 || previousScans.value.length > 0);
const shouldShow = computed(() => {
  if (!props.hideWhenEmpty) {
    return true;
  }

  return isLoading.value || hasAnyScans.value;
});

function applyHistory(payload) {
  history.value = {
    current_visit_scans: payload?.current_visit_scans ?? [],
    previous_scans: payload?.previous_scans ?? [],
  };
}

async function loadHistory() {
  if (!props.patientId) return;

  if (props.clinicalScanHistory) {
    applyHistory(props.clinicalScanHistory);
    return;
  }

  internalLoading.value = true;
  try {
    const { data } = await clinicalScanService.getClinicalScanHistory(props.patientId, {
      current_visit_id: props.currentVisitId || undefined,
    });
    applyHistory(data);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load clinical scan history.');
    applyHistory({ current_visit_scans: [], previous_scans: [] });
  } finally {
    internalLoading.value = false;
  }
}

function emitPrint(scanId) {
  emit('print-scan', scanId);
}

watch(
  () => [props.clinicalScanHistory, props.patientId, props.currentVisitId],
  () => {
    if (props.clinicalScanHistory) {
      applyHistory(props.clinicalScanHistory);
      return;
    }

    loadHistory();
  },
  { deep: true }
);

onMounted(loadHistory);
</script>
