<template>
  <BaseModal :model-value="modelValue" size="lg" @update:model-value="onClose">
    <template #title>
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-teal-600 text-white shadow-sm">
          <AppIcon name="heart" class-name="w-5 h-5 text-white" />
        </span>
        <div class="min-w-0">
          <p class="text-base font-bold text-gray-900 dark:text-white">Record Visit Vitals</p>
          <p class="truncate text-xs font-normal text-gray-500 dark:text-gray-400">
            {{ patientName }}
            <span v-if="mrNumber" class="font-mono"> · MR {{ mrNumber }}</span>
          </p>
        </div>
      </div>
    </template>

    <form id="register-patient-vitals-form" @submit.prevent="submit">
      <VitalsFormFields :form="form" :errors="errors" />
    </form>

    <template #footer>
      <BaseButton type="button" variant="secondary" @click="onClose">Skip</BaseButton>
      <BaseButton type="submit" form="register-patient-vitals-form" :loading="saving" :disabled="saving">
        Save Vitals
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { patientVitalService } from '@/services/patientVitalService';
import { useFormErrors } from '@/composables/useFormErrors';
import AppIcon from '@/components/ui/AppIcon.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import VitalsFormFields from '@/components/vitals/VitalsFormFields.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  patientId: { type: [Number, String], default: null },
  visitId: { type: [Number, String], default: null },
  patientName: { type: String, default: '' },
  mrNumber: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'saved', 'closed', 'error']);

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

function resetForm() {
  form.blood_pressure = '';
  form.temperature = '';
  form.weight = '';
  form.pulse_rate = '';
  form.respiratory_rate = '';
  form.notes = '';
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      clearErrors();
      resetForm();
    }
  },
);

function onClose() {
  emit('update:modelValue', false);
  emit('closed');
}

async function submit() {
  if (!props.patientId || !props.visitId) {
    onClose();
    return;
  }

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
    emit('update:modelValue', false);
    emit('saved', vital);
  } catch (e) {
    setErrors(e);
    emit('error', e);
  } finally {
    saving.value = false;
  }
}
</script>
