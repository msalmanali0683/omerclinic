<template>
  <div class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 py-8 px-4">
    <div class="max-w-3xl mx-auto">
      <div class="text-center mb-8">
        <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 items-center justify-center text-white text-2xl font-bold shadow-lg mb-4">
          H+
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Print Lab Reports</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          Enter your MR number and cell phone <strong>or</strong> CNIC to view and print reports.
        </p>
      </div>

      <div v-if="!verified" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
        <form class="space-y-5" @submit.prevent="submitVerify">
          <BaseInput
            v-model="form.mr_number"
            label="MR Number"
            placeholder="Enter your MR number"
            :error="errors.mr_number"
            required
          />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput
              v-model="form.patient_cell"
              label="Cell Phone"
              placeholder="03XX XXXXXXX"
              :error="errors.patient_cell"
              :disabled="!!form.patient_cnic?.trim()"
            />
            <BaseInput
              :model-value="form.patient_cnic"
              label="CNIC"
              placeholder="XXXXX-XXXXXXX-X"
              :error="errors.patient_cnic"
              :disabled="!!form.patient_cell?.trim()"
              @update:model-value="onCnicInput"
            />
          </div>

          <p class="text-xs text-gray-500 dark:text-gray-400">
            Enter MR number plus <strong>either</strong> cell phone <strong>or</strong> CNIC (not both required).
          </p>

          <div v-if="errors.general" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
            {{ errors.general }}
          </div>

          <BaseButton type="submit" class="w-full" :loading="verifying">
            Find My Reports
          </BaseButton>
        </form>
      </div>

      <div v-else class="space-y-6">
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-sm text-teal-600 dark:text-teal-400 font-mono">{{ patient?.mr_number }}</p>
              <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ patient?.patient_name }}</h2>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Verified patient — completed lab reports only
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <BaseButton
                v-if="results.length"
                variant="secondary"
                :loading="printingAll"
                @click="printAll"
              >
                Print All
              </BaseButton>
              <BaseButton variant="ghost" @click="resetSession">New Search</BaseButton>
            </div>
          </div>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Your Lab Reports</h3>
          </div>

          <p v-if="!results.length" class="p-6 text-sm text-gray-500 dark:text-gray-400">
            No completed laboratory reports found for this patient yet.
          </p>

          <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
            <li
              v-for="result in results"
              :key="result.id"
              class="px-5 py-4 flex flex-wrap items-center justify-between gap-3"
            >
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ result.test_name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(result.result_date) }}
                  <span v-if="result.result_time"> · {{ result.result_time }}</span>
                  <span v-if="result.visit?.visit_date"> · Visit {{ formatDate(result.visit.visit_date) }}</span>
                </p>
              </div>
              <BaseButton
                size="sm"
                variant="secondary"
                :loading="printingResultId === result.id"
                @click="printResult(result.id)"
              >
                Print
              </BaseButton>
            </li>
          </ul>
        </section>
      </div>
    </div>

  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { publicLabReportService } from '@/services/publicLabReportService';
import { directPrintLaboratoryReport } from '@/utils/directPrint';
import { useFormErrors } from '@/composables/useFormErrors';
import { formatCnicInput, formatDate } from '@/utils/formatters';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';

const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({
  mr_number: '',
  patient_cell: '',
  patient_cnic: '',
});

const verifying = ref(false);
const verified = ref(false);
const patient = ref(null);
const results = ref([]);
const printingResultId = ref(null);
const printingAll = ref(false);

function onCnicInput(value) {
  form.patient_cnic = formatCnicInput(value);
}

function applyResultsPayload(data) {
  patient.value = data.patient ?? null;
  results.value = data.results ?? [];
  verified.value = true;
}

async function submitVerify() {
  clearErrors();

  const mr = form.mr_number?.trim();
  const cell = form.patient_cell?.trim();
  const cnic = form.patient_cnic?.trim();

  if (!mr) {
    errors.mr_number = 'MR number is required.';
    return;
  }

  if (!cell && !cnic) {
    errors.general = 'Enter your cell phone or CNIC.';
    return;
  }

  verifying.value = true;

  try {
    const payload = { mr_number: mr };
    if (cell) payload.patient_cell = cell;
    if (cnic) payload.patient_cnic = cnic;

    const { data } = await publicLabReportService.verify(payload);
    applyResultsPayload(data);

    if (!results.value.length) {
      toastStore.success('Verified, but no completed reports are available yet.');
    } else {
      toastStore.success('Reports loaded successfully.');
    }
  } catch (e) {
    setErrors(e);
    errors.general = e.response?.data?.message ?? 'Verification failed. Check your details.';
    toastStore.error(errors.general);
  } finally {
    verifying.value = false;
  }
}

async function printResult(resultId) {
  printingResultId.value = resultId;

  try {
    const { data } = await publicLabReportService.getPrintData(resultId);
    await directPrintLaboratoryReport(data.print_data ?? null);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Could not load print data.');
  } finally {
    printingResultId.value = null;
  }
}

async function printAll() {
  printingAll.value = true;

  try {
    const { data } = await publicLabReportService.getAllPrintData();
    await directPrintLaboratoryReport(data.print_data ?? null);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Could not load print data.');
  } finally {
    printingAll.value = false;
  }
}

async function resetSession() {
  try {
    await publicLabReportService.logout();
  } catch {
    // Ignore logout errors.
  }

  verified.value = false;
  patient.value = null;
  results.value = [];
  form.mr_number = '';
  form.patient_cell = '';
  form.patient_cnic = '';
}

onMounted(async () => {
  try {
    const { data } = await publicLabReportService.getResults();
    applyResultsPayload(data);
  } catch {
    verified.value = false;
  }
});
</script>
