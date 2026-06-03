<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
      <BaseSelect v-model="localFilters.report_type" label="Report Type" :options="reportTypeOptions" />
      <BaseSelect v-model="localFilters.filter_by" label="Date Filter By" :options="filterByOptions" />
      <BaseInput v-model="localFilters.from_date" type="date" label="From Date" />
      <BaseInput v-model="localFilters.to_date" type="date" label="To Date" />
      <BaseInput v-model="localFilters.mr_number" label="MR Number" placeholder="MR number" />
      <BaseInput v-model="localFilters.patient_name" label="Patient Name" placeholder="Patient name" />
      <BaseInput v-model="localFilters.patient_father_name" label="Father Name" placeholder="Father name" />
      <BaseSelect v-model="localFilters.patient_gender" label="Gender" :options="genderOptions" placeholder="All" />
      <BaseInput v-model="localFilters.age_from" type="number" label="Age From" min="0" />
      <BaseInput v-model="localFilters.age_to" type="number" label="Age To" min="0" />
      <BaseSelect v-model="localFilters.age_unit" label="Age Unit" :options="ageUnitOptions" placeholder="Any" />
      <BaseInput v-model="localFilters.patient_cell" label="Cell Number" placeholder="Cell" />
      <BaseInput v-model="localFilters.patient_cnic" label="CNIC" placeholder="CNIC" />
      <BaseSelect v-model="localFilters.status" label="Visit Status" :options="statusOptions" placeholder="All" />
      <BaseSelect
        v-model="localFilters.doctor_id"
        label="Doctor"
        :options="doctorOptions"
        placeholder="All doctors"
      />
      <BaseSelect v-model="localFilters.has_prescription" label="Has Prescription" :options="yesNoOptions" placeholder="All" />
      <BaseSelect v-model="localFilters.has_laboratory_result" label="Has Lab Result" :options="yesNoOptions" placeholder="All" />
      <BaseSelect v-model="localFilters.has_clinical_scan" label="Has Clinical Scan" :options="yesNoOptions" placeholder="All" />
      <BaseInput v-model="localFilters.search" label="Search" placeholder="MR, name, cell, CNIC, address..." class="md:col-span-2 xl:col-span-2" />
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

const reportTypeOptions = [
  { value: 'patient', label: 'Patient-wise' },
  { value: 'visit', label: 'Visit-wise' },
];

const filterByOptions = [
  { value: 'registration_date', label: 'Registration Date' },
  { value: 'visit_date', label: 'Visit Date' },
];

const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' },
];

const ageUnitOptions = [
  { value: 'years', label: 'Years' },
  { value: 'months', label: 'Months' },
  { value: 'days', label: 'Days' },
];

const statusOptions = [
  { value: 'pending_prescription', label: 'Pending Prescription' },
  { value: 'in_consultation', label: 'In Consultation' },
  { value: 'prescribed', label: 'Prescribed' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const yesNoOptions = [
  { value: 'yes', label: 'Yes' },
  { value: 'no', label: 'No' },
];
</script>
