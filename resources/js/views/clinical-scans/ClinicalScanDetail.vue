<template>
  <div class="max-w-6xl">
    <div v-if="loading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <template v-else-if="scan">
      <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <p class="font-mono text-teal-600">{{ scan.patient?.mr_number }}</p>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ scan.patient?.patient_name }}</h2>
          <p class="text-gray-500 text-sm">
            {{ scan.patient?.patient_father_name || '—' }} ·
            {{ formatGender(scan.patient?.patient_gender) }} ·
            {{ displayPatientAge(scan.patient) }} ·
            {{ scan.patient?.patient_cell || '—' }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <span :class="statusClass(scan.status)" class="px-3 py-1 rounded-full text-sm capitalize">
            {{ (scan.status || '').replace(/_/g, ' ') }}
          </span>
          <BaseButton
            v-if="canPrint"
            variant="secondary"
            :loading="printLoading"
            @click="openPrintPreview"
          >
            Print
          </BaseButton>
          <BaseButton
            v-if="authStore.can('edit clinical scans')"
            variant="secondary"
            @click="$router.push(`/clinical-scans/${scan.id}/edit`)"
          >
            Edit
          </BaseButton>
          <BaseButton variant="ghost" @click="$router.push('/clinical-scans')">Back</BaseButton>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Scan Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Template:</dt> {{ scan.scan_template_name || scan.template?.template_name || '—' }}</div>
            <div><dt class="inline font-medium">Scan Date:</dt> {{ formatDate(scan.scan_date) }}</div>
            <div><dt class="inline font-medium">Scan Time:</dt> {{ scan.scan_time || '—' }}</div>
            <div><dt class="inline font-medium">Operator:</dt> {{ scan.scan_operator?.name || '—' }}</div>
          </dl>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Visit Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Visit Date:</dt> {{ scan.visit?.visit_date || '—' }}</div>
            <div><dt class="inline font-medium">Visit Time:</dt> {{ scan.visit?.visit_time || '—' }}</div>
            <div><dt class="inline font-medium">Doctor:</dt> {{ scan.visit?.doctor?.name || 'Unassigned' }}</div>
            <div><dt class="inline font-medium">Status:</dt> {{ (scan.visit?.status || '—').replace(/_/g, ' ') }}</div>
          </dl>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4 mb-6">
        <h3 class="font-semibold text-lg">Findings</h3>
        <dl class="space-y-3">
          <div v-for="value in sortedValues" :key="value.id || value.field_key" class="border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0">
            <dt class="text-sm font-medium text-gray-900 dark:text-white">{{ value.field_label }}</dt>
            <dd class="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-wrap">{{ value.field_value || '—' }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="scan.impression" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm mb-4">
        <h3 class="font-semibold mb-2">Impression</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ scan.impression }}</p>
      </div>

      <div v-if="scan.notes" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h3 class="font-semibold mb-2">Notes</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ scan.notes }}</p>
      </div>
    </template>

    <PrescriptionPrintSettingsModal
      v-model="showPrintModal"
      :print-data="printData"
      title="Clinical Scan Print Preview"
      :show-empty-clinical-scans-as-na="false"
      :redirect-after-close="false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { clinicalScanService } from '@/services/clinicalScanService';
import { displayPatientAge, formatDate, formatGender } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import PrescriptionPrintSettingsModal from '@/components/prescription/PrescriptionPrintSettingsModal.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const scan = ref(null);
const loading = ref(true);
const printData = ref(null);
const canPrint = ref(false);
const showPrintModal = ref(false);
const printLoading = ref(false);

const sortedValues = computed(() =>
  [...(scan.value?.values ?? [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

function statusClass(status) {
  if (status === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (status === 'draft') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
  if (status === 'cancelled') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

async function loadScan() {
  loading.value = true;
  try {
    const { data } = await clinicalScanService.getScan(route.params.id);
    scan.value = data.data ?? data;
    canPrint.value = authStore.can('print clinical scans');
  } catch {
    toastStore.error('Failed to load scan.');
    router.push('/clinical-scans');
  } finally {
    loading.value = false;
  }
}

async function openPrintPreview() {
  printLoading.value = true;
  try {
    const { data } = await clinicalScanService.getPrintData(route.params.id);
    printData.value = data.print_data ?? data.data?.print_data ?? null;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load print data.');
  } finally {
    printLoading.value = false;
  }
}

onMounted(loadScan);
</script>
