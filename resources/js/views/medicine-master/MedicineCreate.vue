<template>
  <div class="max-w-2xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Add Medicine</h2>
    <form class="bg-white dark:bg-gray-800 rounded-xl border p-6 space-y-5 shadow-sm" @submit.prevent="submit">
      <BaseSelect
        v-model="form.mdcn_type"
        label="Medicine Type"
        placeholder="Select medicine type"
        :options="medicineTypeOptions"
        :error="errors.mdcn_type"
        required
      />
      <BaseInput v-model="form.mdcn_name" label="Medicine Name" :error="errors.mdcn_name" required />
      <BaseInput v-model="form.mdcn_size" label="Medicine Size" hint="e.g. 500mg, 5ml" :error="errors.mdcn_size" />
      <BaseSelect v-model="form.mdcn_time_id" label="Dose Time" placeholder="Select dose time" :options="doseTimeOptions" :error="errors.mdcn_time_id" />
      <BaseSelect v-model="form.mdcn_dose_from_meal_id" label="Dose From Meal" placeholder="Select dose from meal" :options="doseFromMealOptions" :error="errors.mdcn_dose_from_meal_id" />
      <div class="flex gap-3">
        <BaseButton type="submit" :loading="saving">Save</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { medicineService } from '@/services/medicineService';
import { medicineDoseTimeService } from '@/services/medicineDoseTimeService';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { MEDICINE_TYPE_OPTIONS } from '@/constants/medicineTypes';

const router = useRouter();
const medicineTypeOptions = MEDICINE_TYPE_OPTIONS;
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
const doseTimeOptions = ref([]);
const doseFromMealOptions = ref([]);

async function submit() {
  clearErrors();
  saving.value = true;
  const payload = { ...form };
  if (!payload.mdcn_time_id) delete payload.mdcn_time_id;
  if (!payload.mdcn_dose_from_meal_id) delete payload.mdcn_dose_from_meal_id;
  try {
    await medicineService.createMedicine(payload);
    toastStore.success('Medicine created.');
    router.push('/medicine-master/medicines');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const [times, meals] = await Promise.all([
      medicineDoseTimeService.getDoseTimeOptions(),
      medicineDoseFromMealService.getDoseFromMealOptions(),
    ]);
    doseTimeOptions.value = (times.data.data ?? []).map((o) => ({ value: o.value, label: o.label }));
    doseFromMealOptions.value = (meals.data.data ?? []).map((o) => ({ value: o.value, label: o.label }));
  } catch { /* optional */ }
});
</script>
