<template>
  <div class="max-w-3xl">
    <div v-if="pageLoading" class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-md dark:border-violet-900/50 dark:bg-gray-800">
      <div class="h-20 animate-pulse bg-gradient-to-r from-violet-200 via-purple-200 to-fuchsia-200 dark:from-violet-900/40 dark:via-purple-900/40 dark:to-fuchsia-900/40" />
      <div class="space-y-4 p-5">
        <div class="h-24 animate-pulse rounded-2xl bg-gray-100 dark:bg-gray-700" />
        <div class="h-24 animate-pulse rounded-2xl bg-gray-100 dark:bg-gray-700" />
        <div class="h-32 animate-pulse rounded-2xl bg-gray-100 dark:bg-gray-700" />
      </div>
    </div>

    <div v-else class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-md dark:border-violet-900/50 dark:bg-gray-800">
      <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 px-5 py-4 text-white">
        <div class="flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
            <AppIcon name="user" class-name="w-6 h-6 text-white" />
          </span>
          <div class="min-w-0">
            <h2 class="text-xl font-bold leading-tight sm:text-2xl">Edit Patient</h2>
            <p class="truncate text-sm text-white/85">{{ form.patient_name || 'Update patient record' }}</p>
          </div>
        </div>
      </div>

      <form class="space-y-5 p-5" @submit.prevent="submit">
        <PatientFormFields :form="form" :errors="errors" />

        <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
          <BaseButton type="submit" class="min-w-[140px]" :loading="saving">Save Changes</BaseButton>
          <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { patientService } from '@/services/patientService';
import { useFormErrors } from '@/composables/useFormErrors';
import { formatCnicInput } from '@/utils/formatters';
import PatientFormFields from '@/components/patients/PatientFormFields.vue';
import AppIcon from '@/components/ui/AppIcon.vue';
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

const saving = ref(false);
const pageLoading = ref(true);

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
