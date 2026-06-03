<template>
  <div class="max-w-6xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Laboratory Test Billing</h2>

    <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4 mb-6">
      <h3 class="font-semibold">1. Select Patient</h3>
      <div class="flex flex-wrap gap-3 items-end">
        <BaseInput v-model="patientSearch" placeholder="Search MR, name, cell..." class="flex-1 min-w-[200px]" @keyup.enter="searchPatients" />
        <BaseSelect v-model="visitFilter" label="Visit Filter" :options="visitFilterOptions" class="w-40" />
        <BaseButton variant="secondary" :loading="searchLoading" @click="searchPatients">Search</BaseButton>
      </div>

      <div v-if="searchLoading" class="h-20 bg-gray-100 dark:bg-gray-700 rounded animate-pulse" />

      <div v-else-if="searchResults.length" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="px-3 py-2 text-left">MR#</th>
              <th class="px-3 py-2 text-left">Patient</th>
              <th class="px-3 py-2 text-left">Visit</th>
              <th class="px-3 py-2 text-left">Select</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="(row, index) in searchResults"
              :key="`${row.patient?.id}-${row.visit?.id || 'none'}-${index}`"
              :class="isRowSelected(row) ? 'bg-teal-50 dark:bg-teal-900/20' : ''"
            >
              <td class="px-3 py-2 font-mono text-teal-600">{{ row.patient?.mr_number }}</td>
              <td class="px-3 py-2">
                <div class="font-medium">{{ row.patient?.patient_name }}</div>
                <div class="text-xs text-gray-500">{{ row.patient?.patient_father_name }}</div>
              </td>
              <td class="px-3 py-2">
                <span v-if="row.visit">{{ row.visit.visit_date }} {{ formatTime(row.visit.visit_time) }}</span>
                <span v-else class="text-amber-600 dark:text-amber-400 text-xs">No visit</span>
              </td>
              <td class="px-3 py-2">
                <BaseButton size="sm" variant="ghost" @click="selectPatientRow(row)">
                  {{ isRowSelected(row) ? 'Selected' : 'Select' }}
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="searchedOnce" class="text-sm text-gray-500">No patients found.</p>
    </section>

    <section v-if="selectedPatient" class="bg-white dark:bg-gray-800 rounded-xl border p-5 shadow-sm space-y-4 mb-6">
      <h3 class="font-semibold">Selected Patient</h3>
      <p class="font-mono text-teal-600">{{ selectedPatient.mr_number }}</p>
      <p class="text-lg font-bold">{{ selectedPatient.patient_name }}</p>

      <div v-if="patientVisits.length" class="space-y-2">
        <BaseSelect
          v-model="selectedVisitId"
          label="Visit"
          :options="visitSelectOptions"
          placeholder="Select visit"
        />
      </div>
      <p v-else class="text-sm text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
        No visit found. This lab bill/report will be linked to patient only.
      </p>
    </section>

    <section v-if="selectedPatient" class="bg-white dark:bg-gray-800 rounded-xl border p-5 shadow-sm space-y-4 mb-6">
      <h3 class="font-semibold">2. Select Tests</h3>
      <div class="flex flex-wrap gap-2">
        <BaseSelect
          v-model="testToAdd"
          label="Add Test"
          :options="availableTestOptions"
          placeholder="Choose test template"
          class="flex-1 min-w-[240px]"
        />
        <BaseButton variant="secondary" :disabled="!testToAdd" @click="addTest">Add</BaseButton>
      </div>

      <table v-if="selectedTests.length" class="min-w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
          <tr>
            <th class="px-3 py-2 text-left">Test</th>
            <th class="px-3 py-2 text-right">Price</th>
            <th class="px-3 py-2 text-left">Remove</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="test in selectedTests" :key="test.id" class="border-t border-gray-200 dark:border-gray-700">
            <td class="px-3 py-2">{{ test.test_name }}</td>
            <td class="px-3 py-2 text-right">
              <input
                v-model.number="test.test_price"
                type="number"
                min="0"
                step="0.01"
                class="w-28 rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm text-right dark:bg-gray-800"
              />
            </td>
            <td class="px-3 py-2">
              <BaseButton size="sm" variant="ghost" @click="removeTest(test.id)">Remove</BaseButton>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-else class="text-sm text-gray-500">No tests selected yet.</p>

      <div class="flex justify-end gap-4 text-sm">
        <div><span class="text-gray-500">Subtotal:</span> <strong>{{ formatCurrency(subtotal) }}</strong></div>
        <div><span class="text-gray-500">Total:</span> <strong class="text-lg">{{ formatCurrency(subtotal) }}</strong></div>
      </div>
    </section>

    <div v-if="selectedPatient && authStore.can('create lab bills')" class="flex gap-3">
      <BaseButton :loading="saving" :disabled="!selectedTests.length" @click="saveBill">
        Save Draft Bill
      </BaseButton>
      <BaseButton v-if="savedBillId && authStore.can('print lab bills')" variant="secondary" @click="openPrint">
        Print Bill
      </BaseButton>
    </div>

    <LaboratoryBillPrintModal
      v-model="showPrintModal"
      :print-data="printData"
      @finished="goToLaboratoryResults"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { laboratoryBillService } from '@/services/laboratoryBillService';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { formatCurrency } from '@/utils/formatters';
import LaboratoryBillPrintModal from '@/components/laboratory/LaboratoryBillPrintModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const patientSearch = ref('');
const visitFilter = ref('latest');
const searchLoading = ref(false);
const searchedOnce = ref(false);
const searchResults = ref([]);
const selectedPatient = ref(null);
const selectedVisitId = ref('');
const patientVisits = ref([]);
const templateOptions = ref([]);
const testToAdd = ref('');
const selectedTests = ref([]);
const saving = ref(false);
const savedBillId = ref(null);
const showPrintModal = ref(false);
const printData = ref(null);

const visitFilterOptions = [
  { value: 'latest', label: 'Latest Visit' },
  { value: 'all', label: 'All Visits' },
];

const subtotal = computed(() =>
  selectedTests.value.reduce((sum, t) => sum + Number(t.test_price || 0), 0)
);

const availableTestOptions = computed(() =>
  templateOptions.value.filter(
    (opt) => !selectedTests.value.some((t) => String(t.id) === String(opt.value))
  )
);

const visitSelectOptions = computed(() =>
  patientVisits.value.map((v) => ({
    value: String(v.id),
    label: `${v.visit_date || '—'} ${formatTime(v.visit_time)} · ${(v.status || '').replace(/_/g, ' ')}`,
  }))
);

function formatTime(time) {
  return time ? String(time).slice(0, 5) : '';
}

function isRowSelected(row) {
  if (!selectedPatient.value) return false;
  if (selectedPatient.value.id !== row.patient?.id) return false;
  const visitId = row.visit?.id ? String(row.visit.id) : '';
  return selectedVisitId.value === visitId;
}

async function searchPatients() {
  searchLoading.value = true;
  searchedOnce.value = true;
  try {
    const { data } = await laboratoryBillService.searchPatients({
      search: patientSearch.value || undefined,
      visit_filter: visitFilter.value,
    });
    searchResults.value = data.data ?? [];
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Patient search failed.');
    searchResults.value = [];
  } finally {
    searchLoading.value = false;
  }
}

async function selectPatientRow(row) {
  selectedPatient.value = row.patient;

  const { data } = await laboratoryBillService.searchPatients({
    visit_filter: 'all',
    search: row.patient?.mr_number,
    limit: 100,
  });

  patientVisits.value = (data.data ?? [])
    .filter((r) => r.patient?.id === row.patient.id && r.visit)
    .map((r) => r.visit);

  if (row.visit) {
    selectedVisitId.value = String(row.visit.id);
  } else if (patientVisits.value.length === 1) {
    selectedVisitId.value = String(patientVisits.value[0].id);
  } else {
    selectedVisitId.value = '';
  }
}

function addTest() {
  const selectedId = String(testToAdd.value ?? '').trim();
  if (!selectedId) {
    toastStore.error('Please select a test to add.');
    return;
  }

  const option = templateOptions.value.find((o) => String(o.value) === selectedId);
  if (!option) {
    toastStore.error('Selected test could not be found.');
    return;
  }

  if (selectedTests.value.some((t) => String(t.id) === selectedId)) {
    toastStore.error('This test is already in the list.');
    return;
  }

  selectedTests.value.push({
    id: Number(option.value),
    test_name: option.label,
    test_price: Number(option.test_price ?? 0),
  });
  testToAdd.value = '';
}

function removeTest(id) {
  selectedTests.value = selectedTests.value.filter((t) => String(t.id) !== String(id));
}

async function saveBill() {
  if (!selectedPatient.value || !selectedTests.value.length) {
    toastStore.error('Select a patient and at least one test.');
    return;
  }

  saving.value = true;
  try {
    const payload = {
      patient_id: selectedPatient.value.id,
      test_items: selectedTests.value.map((t) => ({
        template_id: t.id,
        test_price: Number(t.test_price ?? 0),
      })),
    };
    if (selectedVisitId.value) {
      payload.patient_visit_id = Number(selectedVisitId.value);
    }

    const { data } = await laboratoryBillService.createBill(payload);
    savedBillId.value = data.data?.id;
    printData.value = data.print_data;
    toastStore.success(data.message ?? 'Draft bill saved.');
    showPrintModal.value = true;
  } catch (e) {
    const errs = e.response?.data?.errors;
    if (errs) {
      const first = Object.values(errs).flat()[0];
      toastStore.error(first ?? 'Failed to save bill.');
    } else {
      toastStore.error(e.response?.data?.message ?? 'Failed to save bill.');
    }
  } finally {
    saving.value = false;
  }
}

async function openPrint() {
  if (!savedBillId.value) return;
  try {
    const { data } = await laboratoryBillService.getPrintData(savedBillId.value);
    printData.value = data.print_data;
    showPrintModal.value = true;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Unable to load bill for printing.');
  }
}

function goToLaboratoryResults() {
  const query = selectedPatient.value?.id ? { patient_id: selectedPatient.value.id } : {};
  router.push({ path: '/laboratory-results', query });
}

async function loadTemplates() {
  const { data } = await laboratoryTestTemplateService.getTemplateOptions();
  templateOptions.value = (data.data ?? data ?? []).map((t) => ({
    value: String(t.id ?? t.value),
    label: t.label ?? t.test_name,
    test_price: t.test_price,
  }));
}

watch(visitFilter, () => {
  if (searchedOnce.value) searchPatients();
});

onMounted(async () => {
  await loadTemplates();
  await searchPatients();
});
</script>
