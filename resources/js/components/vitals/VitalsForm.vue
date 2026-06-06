<template>
  <div class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-md dark:border-sky-900/50 dark:bg-gray-800">
    <div class="bg-gradient-to-r from-sky-600 via-cyan-600 to-teal-600 px-5 py-4 text-white">
      <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
          <AppIcon name="heart" class-name="w-6 h-6 text-white" />
        </span>
        <div>
          <h3 class="text-lg font-bold leading-tight">Record Vitals</h3>
          <p class="text-sm text-white/85">Capture blood pressure, temperature, weight, pulse, and respiration</p>
        </div>
      </div>
    </div>

    <form class="space-y-5 p-5" @submit.prevent="submit">
      <VitalsFormFields :form="form" :errors="errors" />

      <div class="border-t border-gray-100 pt-4 dark:border-gray-700">
        <BaseButton type="submit" class="min-w-[140px]" :loading="saving" :disabled="saving">
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
import AppIcon from '@/components/ui/AppIcon.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import VitalsFormFields from '@/components/vitals/VitalsFormFields.vue';

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
