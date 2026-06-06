<template>
  <div class="space-y-3">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('emerald').card">
        <FieldHeader title="Patient Name" subtitle="Full legal name" color="emerald" required />
        <input
          v-model="form.patient_name"
          type="text"
          :class="getPatientFieldStyle('emerald').input"
          placeholder="Enter patient name"
        />
        <FieldError :message="fieldError('patient_name')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('teal').card">
        <FieldHeader title="S/o, W/o, D/o" subtitle="Son / Wife / Daughter of" color="teal" />
        <input
          v-model="form.patient_father_name"
          type="text"
          :class="getPatientFieldStyle('teal').input"
          placeholder="Father / husband / guardian name"
        />
        <FieldError :message="fieldError('patient_father_name')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('violet').card">
        <FieldHeader title="Gender" subtitle="Select patient gender" color="violet" required />
        <select v-model="form.patient_gender" :class="getPatientFieldStyle('violet').input">
          <option value="" disabled>Select gender</option>
          <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <FieldError :message="fieldError('patient_gender')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('fuchsia').card">
        <FieldHeader title="Age" subtitle="Years, months, or days" color="fuchsia" required />
        <div class="grid grid-cols-5 gap-2">
          <div class="col-span-3">
            <input
              v-model="form.patient_age"
              type="number"
              min="0"
              max="150"
              :class="getPatientFieldStyle('fuchsia').input"
              placeholder="0"
            />
          </div>
          <div class="col-span-2">
            <select v-model="form.patient_age_unit" :class="getPatientFieldStyle('fuchsia').input">
              <option v-for="opt in ageUnitOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
        </div>
        <FieldError :message="fieldError('patient_age') || fieldError('patient_age_unit')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('blue').card">
        <FieldHeader title="Cell Number" subtitle="Primary contact number" color="blue" required />
        <input
          v-model="form.patient_cell"
          type="text"
          :class="getPatientFieldStyle('blue').input"
          placeholder="03XX-XXXXXXX"
        />
        <FieldError :message="fieldError('patient_cell')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm" :class="getPatientFieldStyle('sky').card">
        <FieldHeader title="CNIC" subtitle="e.g. 35202-1234567-1" color="sky" />
        <input
          :value="form.patient_cnic"
          type="text"
          placeholder="XXXXX-XXXXXXX-X"
          :class="getPatientFieldStyle('sky').input"
          @input="onCnicInput($event.target.value)"
        />
        <FieldError :message="fieldError('patient_cnic')" />
      </div>

      <div class="rounded-2xl border p-4 shadow-sm md:col-span-2" :class="getPatientFieldStyle('indigo').card">
        <FieldHeader title="Address" subtitle="Residential or mailing address" color="indigo" />
        <textarea
          v-model="form.patient_address"
          rows="2"
          :class="getPatientFieldStyle('indigo').input"
          placeholder="Street, area, city..."
        />
        <FieldError :message="fieldError('patient_address')" />
      </div>
    </div>

    <slot name="after-fields" />
  </div>
</template>

<script setup>
import FieldError from '@/components/patients/PatientFormFieldError.vue';
import FieldHeader from '@/components/patients/PatientFormFieldHeader.vue';
import { getPatientFieldStyle } from '@/utils/patientFieldTheme';
import { AGE_UNIT_OPTIONS, formatCnicInput, GENDER_OPTIONS } from '@/utils/formatters';

const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
});

const genderOptions = GENDER_OPTIONS;
const ageUnitOptions = AGE_UNIT_OPTIONS;

function fieldError(key) {
  const err = props.errors[key];

  return Array.isArray(err) ? err[0] : (err ?? '');
}

function onCnicInput(value) {
  props.form.patient_cnic = formatCnicInput(value);
}
</script>
