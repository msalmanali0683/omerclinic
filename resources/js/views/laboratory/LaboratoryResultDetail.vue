<template>
  <div class="max-w-6xl">
    <div v-if="loading" class="h-64 bg-gray-200 dark:bg-gray-700 rounded-xl animate-pulse" />

    <template v-else-if="result">
      <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <p class="font-mono text-teal-600">{{ result.patient?.mr_number }}</p>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ result.patient?.patient_name }}</h2>
          <p class="text-gray-500 text-sm">
            {{ result.patient?.patient_father_name || '—' }} ·
            {{ formatGender(result.patient?.patient_gender) }} ·
            {{ displayPatientAge(result.patient) }} ·
            {{ result.patient?.patient_cell || '—' }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <span :class="statusClass(result.status)" class="px-3 py-1 rounded-full text-sm capitalize">
            {{ (result.status || '').replace(/_/g, ' ') }}
          </span>
          <BaseButton
            v-if="canPrint"
            variant="secondary"
            :loading="printLoading"
            @click="openPrintPreview"
          >
            Print Report
          </BaseButton>
          <BaseButton
            v-if="authStore.can('verify laboratory results') && result.status !== 'verified' && result.status !== 'cancelled'"
            variant="secondary"
            :loading="verifyLoading"
            @click="verifyResult"
          >
            Verify
          </BaseButton>
          <BaseButton
            v-if="authStore.can('edit laboratory results')"
            variant="secondary"
            @click="$router.push(`/laboratory-results/${result.id}/edit`)"
          >
            Edit
          </BaseButton>
          <BaseButton variant="ghost" @click="$router.push('/laboratory-results')">Back</BaseButton>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Laboratory Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Test:</dt> {{ result.test_name || result.template?.test_name || '—' }}</div>
            <div><dt class="inline font-medium">Code:</dt> {{ result.test_code || result.template?.test_code || '—' }}</div>
            <div><dt class="inline font-medium">Price:</dt> {{ formatCurrency(result.test_price) }}</div>
            <div><dt class="inline font-medium">Result Date:</dt> {{ formatDate(result.result_date) }}</div>
            <div><dt class="inline font-medium">Result Time:</dt> {{ result.result_time || '—' }}</div>
            <div><dt class="inline font-medium">Operator:</dt> {{ result.lab_operator?.name || '—' }}</div>
          </dl>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <h3 class="font-semibold mb-2">Visit Info</h3>
          <dl class="text-sm space-y-1 text-gray-600 dark:text-gray-400">
            <div><dt class="inline font-medium">Visit Date:</dt> {{ result.visit?.visit_date || '—' }}</div>
            <div><dt class="inline font-medium">Visit Time:</dt> {{ result.visit?.visit_time || '—' }}</div>
            <div><dt class="inline font-medium">Doctor:</dt> {{ result.visit?.doctor?.name || 'Unassigned' }}</div>
            <div><dt class="inline font-medium">Status:</dt> {{ (result.visit?.status || '—').replace(/_/g, ' ') }}</div>
          </dl>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4 mb-6">
        <h3 class="font-semibold text-lg">{{ isImagingTest ? 'X-Ray Report' : 'Values' }}</h3>

        <LaboratoryXrayPreview
          v-if="isImagingTest"
          :values="sortedValues"
        />

        <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Field</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Value</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Unit</th>
                <th class="px-3 py-2 text-left font-semibold text-gray-500">Normal Range</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="value in sortedValues" :key="value.id || value.field_key">
                <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ value.field_label }}</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ value.field_value || '—' }}</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ value.unit || '—' }}</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ value.reference_range || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="result.remarks" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
        <h3 class="font-semibold mb-2">Remarks</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ result.remarks }}</p>
      </div>
    </template>

    <LaboratoryResultPrintModal
      v-model="showPrintModal"
      :print-data="printData"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';
import { displayPatientAge, formatDate, formatGender, formatCurrency } from '@/utils/formatters';
import { isImagingTestType } from '@/utils/laboratory';
import BaseButton from '@/components/ui/BaseButton.vue';
import LaboratoryResultPrintModal from '@/components/laboratory/LaboratoryResultPrintModal.vue';
import LaboratoryXrayPreview from '@/components/laboratory/LaboratoryXrayPreview.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const result = ref(null);
const loading = ref(true);
const verifyLoading = ref(false);
const printData = ref(null);
const canPrint = ref(false);
const showPrintModal = ref(false);
const printLoading = ref(false);

const sortedValues = computed(() =>
  [...(result.value?.values ?? [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

const isImagingTest = computed(() =>
  isImagingTestType(result.value?.test_type || result.value?.template?.test_type)
);

function statusClass(status) {
  if (status === 'verified') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
  if (status === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
  if (status === 'draft') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
  if (status === 'cancelled') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

async function loadResult() {
  loading.value = true;
  try {
    const { data } = await laboratoryResultService.getResult(route.params.id);
    result.value = data.data ?? data;
    canPrint.value = authStore.can('print laboratory results');
  } catch {
    toastStore.error('Failed to load laboratory result.');
    router.push('/laboratory-results');
  } finally {
    loading.value = false;
  }
}

async function verifyResult() {
  verifyLoading.value = true;
  try {
    const { data } = await laboratoryResultService.verifyResult(route.params.id);
    result.value = data.data ?? result.value;
    toastStore.success('Laboratory result verified successfully.');
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to verify laboratory result.');
  } finally {
    verifyLoading.value = false;
  }
}

async function openPrintPreview() {
  printLoading.value = true;
  try {
    const { data } = await laboratoryResultService.getPrintData(route.params.id);
    printData.value = data.print_data ?? data.data?.print_data ?? null;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load print data.');
  } finally {
    printLoading.value = false;
  }
}

onMounted(loadResult);
</script>
