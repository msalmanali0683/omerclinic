<template>
  <form class="space-y-3" @submit.prevent="addItem">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ label }}</label>
    <div class="relative">
      <input
        v-model="query"
        type="text"
        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2 text-sm dark:bg-gray-800"
        :class="{ 'border-red-500': error }"
        :placeholder="placeholder"
        autocomplete="off"
        @input="onInput"
        @focus="showDropdown = true"
        @blur="onBlur"
      />
      <ul
        v-if="showDropdown && (options.length || (query.trim() && !loading))"
        class="absolute z-20 mt-1 w-full max-h-48 overflow-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg text-sm"
      >
        <li
          v-for="opt in options"
          :key="opt.id"
          class="px-3 py-2 cursor-pointer hover:bg-teal-50 dark:hover:bg-teal-900/20"
          @mousedown.prevent="selectOption(opt)"
        >
          {{ opt.label }}
        </li>
        <li
          v-if="query.trim() && !exactMatch"
          class="px-3 py-2 cursor-pointer text-teal-700 dark:text-teal-300 hover:bg-teal-50 dark:hover:bg-teal-900/20 border-t border-gray-100 dark:border-gray-700"
          @mousedown.prevent="() => addItem()"
        >
          Add "{{ query.trim() }}" as new complaint
        </li>
      </ul>
    </div>
    <p v-if="fieldError" class="text-sm text-red-600">{{ fieldError }}</p>
    <p v-else-if="error" class="text-sm text-red-600">{{ error }}</p>
    <BaseButton type="button" :loading="addingComplaint" :disabled="!query.trim() || addingComplaint" @click="() => addItem()">
      Add Complaint
    </BaseButton>
  </form>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { complaintMasterService } from '@/services/complaintMasterService';
import { patientVisitComplaintService } from '@/services/patientVisitComplaintService';
import BaseButton from '@/components/ui/BaseButton.vue';

const props = defineProps({
  patientId: { type: [Number, String], required: true },
  visitId: { type: [Number, String], required: true },
  label: { type: String, default: 'Complaint' },
  placeholder: { type: String, default: 'Search or type complaint...' },
});

const emit = defineEmits(['complaint-added']);

const toastStore = useToastStore();
const query = ref('');
const options = ref([]);
const selectedMaster = ref(null);
const loading = ref(false);
const addingComplaint = ref(false);
const error = ref('');
const fieldError = ref('');
const showDropdown = ref(false);
let searchTimer = null;

const exactMatch = computed(() =>
  options.value.some((o) => o.label.toLowerCase() === query.value.trim().toLowerCase())
);

function onInput() {
  selectedMaster.value = null;
  error.value = '';
  fieldError.value = '';
  clearTimeout(searchTimer);
  searchTimer = setTimeout(fetchOptions, 250);
}

async function fetchOptions() {
  const term = query.value.trim();
  if (!term) {
    options.value = [];
    return;
  }
  loading.value = true;
  try {
    const { data } = await complaintMasterService.getComplaintOptions({ search: term, limit: 15 });
    options.value = data.data ?? [];
    showDropdown.value = true;
  } catch {
    options.value = [];
  } finally {
    loading.value = false;
  }
}

function selectOption(opt) {
  query.value = opt.label;
  selectedMaster.value = opt;
  showDropdown.value = false;
}

function onBlur() {
  setTimeout(() => { showDropdown.value = false; }, 150);
}

function handleApiError(e, fallbackMessage) {
  if (e.response?.status === 403) {
    toastStore.error('You are not authorized to perform this action.');
    error.value = 'You are not authorized to perform this action.';
    return;
  }

  const errors = e.response?.data?.errors ?? {};
  fieldError.value = errors.complaint_text?.[0] ?? '';
  const message = fieldError.value || e.response?.data?.message || fallbackMessage;
  error.value = message;
  toastStore.error(message);
}

async function addItem(force = false) {
  const text = query.value.trim();
  if (!text || addingComplaint.value) return;

  addingComplaint.value = true;
  error.value = '';
  fieldError.value = '';
  try {
    let masterId = selectedMaster.value?.id ?? null;
    if (!masterId) {
      const { data: masterRes } = await complaintMasterService.findOrCreateComplaint({ complaint_name: text });
      masterId = masterRes.data.id;
    }

    const payload = {
      patient_id: props.patientId,
      patient_visit_id: props.visitId,
      complaint_master_id: masterId,
      complaint_text: text,
    };
    if (force) {
      payload.force = true;
    }

    const { data } = await patientVisitComplaintService.createVisitComplaint(payload);
    const complaint = data.data ?? data;

    toastStore.success(data.message || 'Complaint added successfully.');
    query.value = '';
    selectedMaster.value = null;
    options.value = [];
    emit('complaint-added', complaint);
  } catch (e) {
    if (e.response?.status === 422 && e.response?.data?.code === 'duplicate_visit_complaint') {
      if (confirm('This complaint is already on this visit. Add again anyway?')) {
        await addItem(true);
        return;
      }
      return;
    }
    handleApiError(e, 'Failed to add complaint.');
  } finally {
    addingComplaint.value = false;
  }
}
</script>
