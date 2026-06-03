<template>
  <BaseModal v-model="open" title="Print Patient Token" size="sm">
    <div class="space-y-4">
      <div class="no-print flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Paper width</label>
        <select
          v-model="paperWidth"
          class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
        >
          <option value="80mm">80mm</option>
          <option value="58mm">58mm</option>
        </select>
      </div>

      <div class="flex justify-center overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white p-4">
        <PatientTokenPrintPreview
          :patient-name="printData.patient_name"
          :father-name="printData.patient_father_name"
          :mr-number="printData.mr_number"
          :token-number="printData.token_number"
          :token-display="printData.token_display"
          :token-date="printData.token_date"
          :visit-date="printData.visit_date"
        />
      </div>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="close">Close</BaseButton>
      <BaseButton :loading="printing" @click="handlePrint">Print</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import PatientTokenPrintPreview from '@/components/tokens/PatientTokenPrintPreview.vue';
import { printPatientTokenElement } from '@/utils/printPatientToken';
import { useToastStore } from '@/stores/toast';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  printData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue', 'printed']);

const toastStore = useToastStore();
const printing = ref(false);
const paperWidth = ref('80mm');

const open = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

watch(() => props.modelValue, (value) => {
  if (value) {
    paperWidth.value = '80mm';
  }
});

function close() {
  open.value = false;
}

async function handlePrint() {
  printing.value = true;

  try {
    await printPatientTokenElement('patient-token-print-area', {
      paperWidth: paperWidth.value,
    }, {
      onAfterPrint: () => {
        emit('printed');
        close();
      },
    });
  } catch (error) {
    toastStore.error(error.message ?? 'Failed to print token.');
  } finally {
    printing.value = false;
  }
}
</script>
