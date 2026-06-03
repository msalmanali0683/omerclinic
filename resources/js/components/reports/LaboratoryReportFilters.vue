<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
      <BaseInput v-model="localFilters.from_date" type="date" label="From Date" />
      <BaseInput v-model="localFilters.to_date" type="date" label="To Date" />
      <BaseInput v-model="localFilters.mr_number" label="MR Number" placeholder="MR number" />
      <BaseInput v-model="localFilters.patient_name" label="Patient Name" placeholder="Patient name" />
      <BaseInput v-model="localFilters.patient_father_name" label="Father Name" placeholder="Father name" />
      <BaseSelect v-model="localFilters.patient_gender" label="Gender" :options="genderOptions" placeholder="All" />
      <BaseInput v-model="localFilters.test_name" label="Test Name" placeholder="Test name" />
      <BaseInput v-model="localFilters.test_code" label="Test Code" placeholder="Test code" />
      <BaseSelect v-model="localFilters.status" label="Result Status" :options="statusOptions" placeholder="All" />
      <BaseSelect
        v-model="localFilters.doctor_id"
        label="Doctor"
        :options="doctorOptions"
        placeholder="All doctors"
      />
      <BaseInput v-model="localFilters.search" label="Search" placeholder="MR, name, test..." class="md:col-span-2 xl:col-span-2" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
  doctorOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const localFilters = reactive({ ...props.modelValue });

watch(
  () => props.modelValue,
  (value) => Object.assign(localFilters, value),
  { deep: true }
);

watch(
  localFilters,
  (value) => emit('update:modelValue', { ...value }),
  { deep: true }
);

const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' },
];

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'completed', label: 'Completed' },
  { value: 'verified', label: 'Verified' },
  { value: 'cancelled', label: 'Cancelled' },
];
</script>
