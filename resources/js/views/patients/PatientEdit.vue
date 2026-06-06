<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Patient</h2>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ form.patient_name }}</p>
    </div>

    <div v-if="pageLoading" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded-xl" />

    <form v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-5" @submit.prevent="submit">
      <BaseInput v-model="form.patient_name" label="Patient Name" :error="errors.patient_name" required />
      <BaseInput v-model="form.patient_father_name" label="S/o, W/o, D/o" :error="errors.patient_father_name" />
      <BaseSelect
        v-model="form.patient_gender"
        label="Gender"
        placeholder="Select gender"
        :options="genderOptions"
        :error="errors.patient_gender"
        required
      />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <BaseInput v-model="form.patient_age" label="Age" type="number" min="0" max="150" :error="errors.patient_age" required />
        <BaseSelect
          v-model="form.patient_age_unit"
          label="Age Unit"
          :options="ageUnitOptions"
          :error="errors.patient_age_unit"
          required
        />
      </div>
      <BaseInput v-model="form.patient_cell" label="Cell Number" :error="errors.patient_cell" required />
      <BaseInput
        :model-value="form.patient_cnic"
        label="CNIC"
        hint="e.g. 35202-1234567-1"
        placeholder="XXXXX-XXXXXXX-X"
        :error="errors.patient_cnic"
        @update:model-value="onCnicInput"
      />
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
        <textarea
          v-model="form.patient_address"
          rows="3"
          class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:outline-none"
        />
        <p v-if="errors.patient_address" class="mt-1 text-sm text-red-600">{{ errors.patient_address }}</p>
      </div>

      <div class="flex gap-3 pt-2">
        <BaseButton type="submit" :loading="saving">Save Changes</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { useFormErrors } from '@/composables/useFormErrors';
import { AGE_UNIT_OPTIONS, formatCnicInput, GENDER_OPTIONS } from '@/utils/formatters';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({
  patient_name: '',
  patient_father_name: '',
  patient_gender: '',
  patient_age: '',
  patient_age_unit: 'years',
  patient_cell: '',
  patient_address: '',
  patient_cnic: '',
});

const genderOptions = GENDER_OPTIONS;
const ageUnitOptions = AGE_UNIT_OPTIONS;

const saving = ref(false);
const pageLoading = ref(true);

function onCnicInput(value) {
  form.patient_cnic = formatCnicInput(value);
}

async function submit() {
  clearErrors();
  saving.value = true;
  try {
    await patientService.updatePatient(route.params.id, {
      ...form,
      patient_age: form.patient_age !== '' ? Number(form.patient_age) : undefined,
    });
    toastStore.success('Patient updated successfully.');
    router.push('/patients');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to update patient.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await patientService.getPatient(route.params.id);
    const patient = data.data ?? data;
    Object.assign(form, {
      patient_name: patient.patient_name ?? '',
      patient_father_name: patient.patient_father_name ?? '',
      patient_gender: patient.patient_gender ?? '',
      patient_age: patient.patient_age ?? '',
      patient_age_unit: patient.patient_age_unit ?? 'years',
      patient_cell: patient.patient_cell ?? '',
      patient_address: patient.patient_address ?? '',
      patient_cnic: formatCnicInput(patient.patient_cnic ?? ''),
    });
  } catch {
    toastStore.error('Failed to load patient.');
    router.push('/patients');
  } finally {
    pageLoading.value = false;
  }
});
</script>
