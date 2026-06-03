<template>
  <div class="max-w-xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Add Dose From Meal</h2>
    <form class="bg-white dark:bg-gray-800 rounded-xl border p-6 space-y-5 shadow-sm" @submit.prevent="submit">
      <BaseInput v-model="form.dose_from_meal" label="Dose From Meal" placeholder="Example: کھانے کے بعد" :error="errors.dose_from_meal" required />
      <div class="flex gap-3">
        <BaseButton type="submit" :loading="saving">Save</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { medicineDoseFromMealService } from '@/services/medicineDoseFromMealService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();
const form = reactive({ dose_from_meal: '' });
const saving = ref(false);

async function submit() {
  clearErrors();
  saving.value = true;
  try {
    await medicineDoseFromMealService.createDoseFromMeal(form);
    toastStore.success('Dose from meal created.');
    router.push('/medicine-master/dose-from-meals');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Save failed.');
  } finally {
    saving.value = false;
  }
}
</script>
