<template>
  <div>
    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Visit Complaints</h4>
    <div v-if="!items.length" class="text-sm text-gray-500 dark:text-gray-400 py-2">No complaints recorded for this visit.</div>
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900/40 text-left">
          <tr>
            <th class="px-3 py-2 font-medium">Complaint</th>
            <th class="px-3 py-2 font-medium">Added By</th>
            <th class="px-3 py-2 font-medium">Added At</th>
            <th v-if="canEdit || canDelete" class="px-3 py-2 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="row in items" :key="row.id">
            <td class="px-3 py-2">
              <input
                v-if="editingId === row.id"
                v-model="editForm.complaint_text"
                class="w-full rounded border border-gray-300 dark:border-gray-600 px-2 py-1 text-sm dark:bg-gray-800"
                :class="{ 'border-red-500': editError }"
              />
              <span v-else>{{ row.complaint_text }}</span>
              <p v-if="editingId === row.id && editError" class="text-xs text-red-600 mt-1">{{ editError }}</p>
            </td>
            <td class="px-3 py-2">{{ row.added_by?.name || '—' }}</td>
            <td class="px-3 py-2">{{ formatDate(row.created_at) }}</td>
            <td v-if="canEdit || canDelete" class="px-3 py-2">
              <div v-if="editingId === row.id" class="flex gap-1">
                <BaseButton type="button" size="sm" :loading="updatingComplaint" @click="saveEdit(row)">Save</BaseButton>
                <BaseButton type="button" size="sm" variant="ghost" :disabled="updatingComplaint" @click="cancelEdit">Cancel</BaseButton>
              </div>
              <div v-else class="flex gap-1">
                <BaseButton v-if="canEdit" type="button" size="sm" variant="ghost" @click="startEdit(row)">Edit</BaseButton>
                <BaseButton
                  v-if="canDelete"
                  type="button"
                  size="sm"
                  variant="ghost"
                  :loading="deletingComplaintId === row.id"
                  :disabled="deletingComplaintId === row.id"
                  @click="remove(row)"
                >Delete</BaseButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useToastStore } from '@/stores/toast';
import { patientVisitComplaintService } from '@/services/patientVisitComplaintService';
import BaseButton from '@/components/ui/BaseButton.vue';
import { formatDateTime as formatDate } from '@/utils/formatters';

defineProps({
  items: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
});

const emit = defineEmits(['complaint-updated', 'complaint-deleted']);

const toastStore = useToastStore();
const editingId = ref(null);
const updatingComplaint = ref(false);
const deletingComplaintId = ref(null);
const editError = ref('');
const editForm = reactive({ complaint_text: '' });

function startEdit(row) {
  editingId.value = row.id;
  editForm.complaint_text = row.complaint_text;
  editError.value = '';
}

function cancelEdit() {
  editingId.value = null;
  editError.value = '';
}

function handleApiError(e, fallbackMessage) {
  if (e.response?.status === 403) {
    const message = 'You are not authorized to perform this action.';
    toastStore.error(message);
    editError.value = message;
    return;
  }

  const errors = e.response?.data?.errors ?? {};
  editError.value = errors.complaint_text?.[0] ?? e.response?.data?.message ?? fallbackMessage;
  toastStore.error(editError.value);
}

async function saveEdit(row) {
  updatingComplaint.value = true;
  editError.value = '';
  try {
    const { data } = await patientVisitComplaintService.updateVisitComplaint(row.id, {
      complaint_master_id: row.complaint_master_id,
      complaint_text: editForm.complaint_text,
    });

    toastStore.success(data.message || 'Complaint updated successfully.');
    editingId.value = null;
    emit('complaint-updated', data.data ?? data);
  } catch (e) {
    handleApiError(e, 'Failed to update complaint.');
  } finally {
    updatingComplaint.value = false;
  }
}

async function remove(row) {
  if (!confirm(`Remove "${row.complaint_text}" from this visit?`)) return;

  deletingComplaintId.value = row.id;
  try {
    const { data } = await patientVisitComplaintService.deleteVisitComplaint(row.id);
    toastStore.success(data.message || 'Complaint deleted successfully.');
    emit('complaint-deleted', row.id);
  } catch (e) {
    if (e.response?.status === 403) {
      toastStore.error('You are not authorized to perform this action.');
    } else {
      toastStore.error(e.response?.data?.message ?? 'Failed to delete complaint.');
    }
  } finally {
    deletingComplaintId.value = null;
  }
}
</script>
