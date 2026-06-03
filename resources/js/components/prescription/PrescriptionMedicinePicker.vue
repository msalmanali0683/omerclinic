<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Add from Medicine Master</label>
    <div class="flex flex-col sm:flex-row gap-2">
      <BaseSelect
        v-model="selectedId"
        class="flex-1"
        placeholder="Select a medicine..."
        :options="options"
      />
      <BaseButton type="button" variant="secondary" :disabled="!selectedId" @click="addMedicine">Add to list</BaseButton>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">Selected medicine details are appended to the medicines field. You can still edit the text below.</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { medicineService } from '@/services/medicineService';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const emit = defineEmits(['append']);

const selectedId = ref('');
const options = ref([]);
const catalog = ref([]);

function formatLine(m) {
  const parts = [m.mdcn_type, m.mdcn_name, m.mdcn_size].filter(Boolean).join(' ');
  const timing = m.dose_time ? ` | Time: ${m.dose_time}` : '';
  const meal = m.dose_from_meal ? ` | Meal: ${m.dose_from_meal}` : '';
  return `${parts}${timing}${meal}`;
}

function addMedicine() {
  const m = catalog.value.find((x) => String(x.id) === String(selectedId.value));
  if (!m) return;
  emit('append', formatLine(m));
  selectedId.value = '';
}

onMounted(async () => {
  try {
    const { data } = await medicineService.getMedicineOptions();
    catalog.value = data.data ?? [];
    options.value = catalog.value.map((m) => ({ value: m.id, label: m.label }));
  } catch { /* optional */ }
});
</script>
