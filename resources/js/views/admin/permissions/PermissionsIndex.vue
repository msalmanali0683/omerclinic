<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Permissions</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage system permissions grouped by module</p>
      </div>
      <BaseButton v-if="authStore.can('assign permissions')" @click="openCreate">+ Create Permission</BaseButton>
    </div>

    <BaseInput v-model="search" placeholder="Search permissions..." class="mb-4 max-w-md" />

    <div v-if="loading" class="animate-pulse h-48 bg-gray-200 dark:bg-gray-700 rounded-xl" />

    <div v-else class="space-y-4">
      <div
        v-for="(perms, module) in filteredGroups"
        :key="module"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
      >
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ module }}</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <div
            v-for="perm in perms"
            :key="perm.id"
            class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30"
          >
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ perm.name }}</span>
            <div v-if="authStore.can('assign permissions')" class="flex gap-2">
              <BaseButton variant="ghost" size="sm" @click="openEdit(perm)">Edit</BaseButton>
              <BaseButton variant="ghost" size="sm" @click="confirmDelete(perm)">Delete</BaseButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <BaseModal v-model="formModal.open" :title="formModal.editing ? 'Edit Permission' : 'Create Permission'">
      <BaseInput v-model="formModal.name" label="Permission Name" :error="formErrors.name" required />
      <template #footer>
        <BaseButton variant="secondary" @click="formModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="formModal.saving" @click="savePermission">Save</BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model="deleteModal.open" title="Delete Permission" size="sm">
      <p class="text-gray-600 dark:text-gray-300">Delete permission <strong>{{ deleteModal.permission?.name }}</strong>?</p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deletePermission">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { permissionService } from '@/services/permissionService';
import { groupPermissions } from '@/utils/menu';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseModal from '@/components/ui/BaseModal.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors: formErrors, setErrors, clearErrors } = useFormErrors();

const permissions = ref([]);
const loading = ref(true);
const search = ref('');

const grouped = computed(() => groupPermissions(permissions.value));

const filteredGroups = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return grouped.value;
  const result = {};
  for (const [module, perms] of Object.entries(grouped.value)) {
    const filtered = perms.filter((p) => p.name.toLowerCase().includes(q));
    if (filtered.length) result[module] = filtered;
  }
  return result;
});

const formModal = reactive({ open: false, editing: false, id: null, name: '', saving: false });
const deleteModal = reactive({ open: false, permission: null, deleting: false });

function openCreate() {
  formModal.editing = false;
  formModal.id = null;
  formModal.name = '';
  clearErrors();
  formModal.open = true;
}

function openEdit(perm) {
  formModal.editing = true;
  formModal.id = perm.id;
  formModal.name = perm.name;
  clearErrors();
  formModal.open = true;
}

async function savePermission() {
  clearErrors();
  formModal.saving = true;
  try {
    if (formModal.editing) {
      await permissionService.update(formModal.id, { name: formModal.name });
      toastStore.success('Permission updated.');
    } else {
      await permissionService.create({ name: formModal.name });
      toastStore.success('Permission created.');
    }
    formModal.open = false;
    fetchPermissions();
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to save permission.');
  } finally {
    formModal.saving = false;
  }
}

function confirmDelete(perm) {
  deleteModal.permission = perm;
  deleteModal.open = true;
}

async function deletePermission() {
  deleteModal.deleting = true;
  try {
    await permissionService.remove(deleteModal.permission.id);
    toastStore.success('Permission deleted.');
    deleteModal.open = false;
    fetchPermissions();
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to delete permission.');
  } finally {
    deleteModal.deleting = false;
  }
}

async function fetchPermissions() {
  loading.value = true;
  try {
    const { data } = await permissionService.list();
    permissions.value = data.data ?? data;
  } finally {
    loading.value = false;
  }
}

onMounted(fetchPermissions);
</script>
