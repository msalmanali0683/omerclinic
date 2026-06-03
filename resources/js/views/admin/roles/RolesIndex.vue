<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Roles</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage roles and their permissions</p>
      </div>
      <BaseButton v-if="authStore.can('assign roles')" @click="openCreate">+ Create Role</BaseButton>
    </div>

    <BaseTable :columns="columns" :rows="roles" :loading="loading">
      <template #cell-permissions="{ row }">
        <span class="text-gray-500">{{ row.permissions?.length ?? 0 }} permissions</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex gap-1">
          <BaseButton v-if="authStore.can('assign roles')" variant="ghost" size="sm" @click="openEdit(row)">Edit</BaseButton>
          <BaseButton
            v-if="authStore.can('assign roles') && row.name !== 'super-admin'"
            variant="ghost"
            size="sm"
            @click="confirmDelete(row)"
          >Delete</BaseButton>
        </div>
      </template>
    </BaseTable>

    <BaseModal v-model="formModal.open" :title="formModal.editing ? 'Edit Role' : 'Create Role'" size="xl">
      <div class="space-y-4">
        <BaseInput
          v-model="formModal.name"
          label="Role Name"
          :error="formErrors.name"
          :disabled="formModal.editing && formModal.originalName === 'super-admin'"
          required
        />
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permissions</label>
          <PermissionPicker v-model="formModal.permissions" :permissions="allPermissions" />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="formModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="formModal.saving" @click="saveRole">Save</BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model="deleteModal.open" title="Delete Role" size="sm">
      <p class="text-gray-600 dark:text-gray-300">Delete role <strong>{{ deleteModal.role?.name }}</strong>?</p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deleteRole">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { roleService } from '@/services/roleService';
import { permissionService } from '@/services/permissionService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import PermissionPicker from '@/components/admin/PermissionPicker.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors: formErrors, setErrors, clearErrors } = useFormErrors();

const columns = [
  { key: 'name', label: 'Role' },
  { key: 'permissions', label: 'Permissions' },
  { key: 'actions', label: 'Actions' },
];

const roles = ref([]);
const allPermissions = ref([]);
const loading = ref(true);

const formModal = reactive({
  open: false,
  editing: false,
  id: null,
  name: '',
  originalName: '',
  permissions: [],
  saving: false,
});

const deleteModal = reactive({ open: false, role: null, deleting: false });

async function fetchRoles() {
  loading.value = true;
  try {
    const { data } = await roleService.list();
    roles.value = data.data ?? data;
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  formModal.editing = false;
  formModal.id = null;
  formModal.name = '';
  formModal.originalName = '';
  formModal.permissions = [];
  clearErrors();
  formModal.open = true;
}

function openEdit(role) {
  formModal.editing = true;
  formModal.id = role.id;
  formModal.name = role.name;
  formModal.originalName = role.name;
  formModal.permissions = [...(role.permissions ?? [])];
  clearErrors();
  formModal.open = true;
}

async function saveRole() {
  clearErrors();
  formModal.saving = true;
  try {
    const payload = { name: formModal.name, permissions: formModal.permissions };
    if (formModal.editing) {
      await roleService.update(formModal.id, payload);
      toastStore.success('Role updated successfully.');
    } else {
      await roleService.create(payload);
      toastStore.success('Role created successfully.');
    }
    formModal.open = false;
    fetchRoles();
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to save role.');
  } finally {
    formModal.saving = false;
  }
}

function confirmDelete(role) {
  deleteModal.role = role;
  deleteModal.open = true;
}

async function deleteRole() {
  deleteModal.deleting = true;
  try {
    await roleService.remove(deleteModal.role.id);
    toastStore.success('Role deleted successfully.');
    deleteModal.open = false;
    fetchRoles();
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to delete role.');
  } finally {
    deleteModal.deleting = false;
  }
}

onMounted(async () => {
  await fetchRoles();
  const { data } = await permissionService.list();
  allPermissions.value = data.data ?? data;
});
</script>
