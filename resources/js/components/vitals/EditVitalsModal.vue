<template>
  <BaseModal :model-value="show" title="Edit Current Visit Vitals" @update:model-value="onClose">
    <form id="edit-vitals-form" class="space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <BaseInput v-model="form.blood_pressure" label="B.P" placeholder="120/80" :error="errors.blood_pressure" />
        <BaseInput v-model="form.temperature" label="Temp" type="number" step="0.1" placeholder="98.6" :error="errors.temperature" />
        <BaseInput v-model="form.weight" label="Weight" type="number" step="0.1" placeholder="kg" :error="errors.weight" />
        <BaseInput v-model="form.pulse_rate" label="P/R" type="number" placeholder="72" :error="errors.pulse_rate" />
        <BaseInput v-model="form.respiratory_rate" label="R/R" type="number" placeholder="16" :error="errors.respiratory_rate" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
        <textarea
          v-model="form.notes"
          rows="2"
          class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:outline-none"
        />
        <p v-if="errors.notes" class="text-sm text-red-600 mt-1">{{ errors.notes }}</p>
      </div>
    </form>
    <template #footer>
      <BaseButton type="button" variant="secondary" @click="onClose">Cancel</BaseButton>
      <BaseButton type="submit" form="edit-vitals-form" :loading="saving" :disabled="saving">
        Save Vitals
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { patientVitalService } from '@/services/patientVitalService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  vitals: { type: Object, default: null },
  patientId: { type: [Number, String], required: true },
  visitId: { type: [Number, String], required: true },
});

const emit = defineEmits(['saved', 'close', 'error']);

const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({
  blood_pressure: '',
  temperature: '',
  weight: '',
  pulse_rate: '',
  respiratory_rate: '',
  notes: '',
});

const saving = ref(false);

function fillForm(vital) {
  form.blood_pressure = vital?.blood_pressure ?? '';
  form.temperature = vital?.temperature ?? '';
  form.weight = vital?.weight ?? '';
  form.pulse_rate = vital?.pulse_rate ?? '';
  form.respiratory_rate = vital?.respiratory_rate ?? '';
  form.notes = vital?.notes ?? '';
}

watch(
  () => [props.show, props.vitals],
  ([visible, vital]) => {
    if (visible && vital) {
      clearErrors();
      fillForm(vital);
    }
  },
  { immediate: true }
);

function onClose() {
  emit('close');
}

async function submit() {
  if (!props.vitals?.id) return;

  clearErrors();
  saving.value = true;

  const payload = {
    patient_id: props.patientId,
    patient_visit_id: props.visitId,
    blood_pressure: form.blood_pressure || null,
    temperature: form.temperature || null,
    weight: form.weight || null,
    pulse_rate: form.pulse_rate || null,
    respiratory_rate: form.respiratory_rate || null,
    notes: form.notes || null,
  };

  try {
    const { data } = await patientVitalService.updateVital(props.vitals.id, payload);
    const vital = data.vital ?? data.data ?? data;
    emit('saved', vital);
  } catch (e) {
    setErrors(e);
    emit('error', e);
  } finally {
    saving.value = false;
  }
}
</script>
