<template>
  <div class="max-w-xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit Dose Time</h2>
    <div v-if="pageLoading" class="h-32 animate-pulse bg-gray-200 dark:bg-gray-700 rounded-xl" />
    <form v-else class="bg-white dark:bg-gray-800 rounded-xl border p-6 space-y-5 shadow-sm" @submit.prevent="submit">
      <BaseInput v-model="form.dose_time" label="Dose Time" placeholder="Example: 1+1+1 — صبح+دوپہر+شام" :error="errors.dose_time" required />
      <div class="flex gap-3">
        <BaseButton type="submit" :loading="saving">Update</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { medicineDoseTimeService } from '@/services/medicineDoseTimeService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const route = useRoute();
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();
const form = reactive({ dose_time: '' });
const saving = ref(false);
const pageLoading = ref(true);

async function submit() {
  clearErrors();
  saving.value = true;
  try {
    await medicineDoseTimeService.updateDoseTime(route.params.id, form);
    toastStore.success('Updated successfully.');
    router.push('/medicine-master/dose-times');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Update failed.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await medicineDoseTimeService.getDoseTime(route.params.id);
    const row = data.data ?? data;
    form.dose_time = row.dose_time;
  } catch {
    toastStore.error('Failed to load record.');
    router.push('/medicine-master/dose-times');
  } finally {
    pageLoading.value = false;
  }
});
</script>
