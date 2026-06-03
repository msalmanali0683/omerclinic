<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Record Vitals</h3>

    <form class="space-y-4" @submit.prevent="submit">
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
      <div>
        <BaseButton type="submit" :loading="saving" :disabled="saving">
          Save Vitals
        </BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { patientVitalService } from '@/services/patientVitalService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  patientId: { type: [Number, String], required: true },
  visitId: { type: [Number, String], required: true },
});

const emit = defineEmits(['saved', 'error']);

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

async function submit() {
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
    const { data } = await patientVitalService.createVital(payload);
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
