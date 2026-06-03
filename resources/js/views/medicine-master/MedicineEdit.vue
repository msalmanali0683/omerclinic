<template>
  <div class="max-w-2xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Edit Medicine</h2>
    <div v-if="pageLoading" class="h-48 animate-pulse bg-gray-200 dark:bg-gray-700 rounded-xl" />
    <form v-else class="bg-white dark:bg-gray-800 rounded-xl border p-6 space-y-5 shadow-sm" @submit.prevent="submit">
      <BaseSelect
        v-model="form.mdcn_type"
        label="Medicine Type"
        placeholder="Select medicine type"
        :options="medicineTypeOptions"
        :error="errors.mdcn_type"
        required
      />
      <BaseInput v-model="form.mdcn_name" label="Medicine Name" :error="errors.mdcn_name" required />
      <BaseInput v-model="form.mdcn_size" label="Medicine Size" :error="errors.mdcn_size" />
      <BaseSelect v-model="form.mdcn_time_id" label="Dose Time" placeholder="Select dose time" :options="doseTimeOptions" />
      <BaseSelect v-model="form.mdcn_dose_from_meal_id" label="Dose From Meal" placeholder="Select dose from meal" :options="doseFromMealOptions" />
      <div class="flex gap-3">
        <BaseButton type="submit" :loading="saving">Update</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { medicineService } from '@/services/medicineService';
import { medicineDoseTimeService } from '@/services/medicineDoseTimeService';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { medicineTypeOptionsFor } from '@/constants/medicineTypes';

const route = useRoute();
const medicineTypeOptions = computed(() => medicineTypeOptionsFor(form.mdcn_type));
const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();
const form = reactive({
  mdcn_type: '',
  mdcn_name: '',
  mdcn_size: '',
  mdcn_time_id: '',
  mdcn_dose_from_meal_id: '',
});
const saving = ref(false);
const pageLoading = ref(true);
const doseTimeOptions = ref([]);
const doseFromMealOptions = ref([]);

async function submit() {
  clearErrors();
  saving.value = true;
  const payload = { ...form };
  if (!payload.mdcn_time_id) payload.mdcn_time_id = null;
  if (!payload.mdcn_dose_from_meal_id) payload.mdcn_dose_from_meal_id = null;
  try {
    await medicineService.updateMedicine(route.params.id, payload);
    toastStore.success('Medicine updated.');
    router.push('/medicine-master/medicines');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Update failed.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const [medRes, times, meals] = await Promise.all([
      medicineService.getMedicine(route.params.id),
      medicineDoseTimeService.getDoseTimeOptions(),
      medicineDoseFromMealService.getDoseFromMealOptions(),
    ]);
    const row = medRes.data.data ?? medRes.data;
    form.mdcn_type = row.mdcn_type;
    form.mdcn_name = row.mdcn_name;
    form.mdcn_size = row.mdcn_size ?? '';
    form.mdcn_time_id = row.mdcn_time_id ?? '';
    form.mdcn_dose_from_meal_id = row.mdcn_dose_from_meal_id ?? '';
    doseTimeOptions.value = (times.data.data ?? []).map((o) => ({ value: o.value, label: o.label }));
    doseFromMealOptions.value = (meals.data.data ?? []).map((o) => ({ value: o.value, label: o.label }));
  } catch {
    toastStore.error('Failed to load medicine.');
    router.push('/medicine-master/medicines');
  } finally {
    pageLoading.value = false;
  }
});
</script>
