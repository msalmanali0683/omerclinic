<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage system users, roles, and permissions</p>
      </div>
      <BaseButton v-if="authStore.can('create users')" @click="$router.push('/admin/users/create')">
        + Create User
      </BaseButton>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 shadow-sm">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <BaseInput v-model="filters.search" placeholder="Search by name or email..." @keyup.enter="fetchUsers" />
        <BaseSelect
          v-model="filters.role"
          placeholder="All roles"
          :options="roleOptions"
          @change="fetchUsers"
        />
        <BaseButton variant="secondary" @click="fetchUsers">Search</BaseButton>
      </div>
    </div>

    <BaseTable :columns="columns" :rows="users" :loading="loading">
      <template #cell-roles="{ row }">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="role in row.roles"
            :key="role"
            class="px-2 py-0.5 rounded-full text-xs bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300"
          >{{ role }}</span>
        </div>
      </template>
      <template #cell-permissions_count="{ row }">
        {{ row.permissions?.length ?? 0 }}
      </template>
      <template #cell-created_at="{ row }">
        {{ formatDate(row.created_at) }}
      </template>
      <template #cell-actions="{ row }">
        <div class="flex items-center gap-1">
          <BaseButton v-if="authStore.can('edit users')" variant="ghost" size="sm" @click="$router.push(`/admin/users/${row.id}/edit`)">Edit</BaseButton>
          <BaseButton v-if="authStore.can('assign roles')" variant="ghost" size="sm" @click="openRolesModal(row)">Roles</BaseButton>
          <BaseButton v-if="authStore.can('assign permissions')" variant="ghost" size="sm" @click="openPermissionsModal(row)">Perms</BaseButton>
          <BaseButton v-if="authStore.can('delete users')" variant="ghost" size="sm" @click="confirmDelete(row)">Delete</BaseButton>
        </div>
      </template>
    </BaseTable>

    <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-4">
      <p class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
      <div class="flex gap-2">
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">Previous</BaseButton>
        <BaseButton variant="secondary" size="sm" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">Next</BaseButton>
      </div>
    </div>

    <!-- Assign Roles Modal -->
    <BaseModal v-model="rolesModal.open" title="Assign Roles" size="md">
      <div class="space-y-2">
        <label v-for="role in allRoles" :key="role.name" class="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            :value="role.name"
            :checked="rolesModal.selected.includes(role.name)"
            class="rounded border-gray-300 text-teal-600"
            @change="toggleRole(role.name)"
          />
          <span>{{ role.name }}</span>
        </label>
      </div>
      <template #footer>
        <BaseButton variant="secondary" @click="rolesModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="rolesModal.saving" @click="saveRoles">Save Roles</BaseButton>
      </template>
    </BaseModal>

    <!-- Assign Permissions Modal -->
    <BaseModal v-model="permsModal.open" title="Assign Permissions" size="xl">
      <PermissionPicker v-model="permsModal.selected" :permissions="allPermissions" />
      <template #footer>
        <BaseButton variant="secondary" @click="permsModal.open = false">Cancel</BaseButton>
        <BaseButton :loading="permsModal.saving" @click="savePermissions">Save Permissions</BaseButton>
      </template>
    </BaseModal>

    <!-- Delete Confirmation -->
    <BaseModal v-model="deleteModal.open" title="Delete User" size="sm">
      <p class="text-gray-600 dark:text-gray-300">
        Are you sure you want to delete <strong>{{ deleteModal.user?.name }}</strong>? This action cannot be undone.
      </p>
      <template #footer>
        <BaseButton variant="secondary" @click="deleteModal.open = false">Cancel</BaseButton>
        <BaseButton variant="danger" :loading="deleteModal.deleting" @click="deleteUser">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { userService } from '@/services/userService';
import { roleService } from '@/services/roleService';
import { permissionService } from '@/services/permissionService';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import BaseTable from '@/components/ui/BaseTable.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import PermissionPicker from '@/components/admin/PermissionPicker.vue';
import { formatDate } from '@/utils/formatters';

const authStore = useAuthStore();
const toastStore = useToastStore();

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'roles', label: 'Roles' },
  { key: 'permissions_count', label: 'Permissions' },
  { key: 'created_at', label: 'Created' },
  { key: 'actions', label: 'Actions' },
];

const users = ref([]);
const loading = ref(true);
const allRoles = ref([]);
const allPermissions = ref([]);
const roleOptions = ref([{ value: '', label: 'All roles' }]);

const filters = reactive({ search: '', role: '' });
const pagination = reactive({ current_page: 1, last_page: 1 });

const rolesModal = reactive({ open: false, user: null, selected: [], saving: false });
const permsModal = reactive({ open: false, user: null, selected: [], saving: false });
const deleteModal = reactive({ open: false, user: null, deleting: false });

async function fetchUsers(page = 1) {
  loading.value = true;
  try {
    const { data } = await userService.list({
      search: filters.search || undefined,
      role: filters.role || undefined,
      page,
    });
    users.value = data.data;
    pagination.current_page = data.meta?.current_page ?? 1;
    pagination.last_page = data.meta?.last_page ?? 1;
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to load users.');
  } finally {
    loading.value = false;
  }
}

function goPage(page) {
  fetchUsers(page);
}

function openRolesModal(user) {
  rolesModal.user = user;
  rolesModal.selected = [...(user.roles ?? [])];
  rolesModal.open = true;
}

function toggleRole(name) {
  if (rolesModal.selected.includes(name)) {
    rolesModal.selected = rolesModal.selected.filter((r) => r !== name);
  } else {
    rolesModal.selected.push(name);
  }
}

async function saveRoles() {
  rolesModal.saving = true;
  try {
    await userService.syncRoles(rolesModal.user.id, rolesModal.selected);
    toastStore.success('Roles updated successfully.');
    rolesModal.open = false;
    fetchUsers(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to update roles.');
  } finally {
    rolesModal.saving = false;
  }
}

function openPermissionsModal(user) {
  permsModal.user = user;
  permsModal.selected = [...(user.permissions ?? [])];
  permsModal.open = true;
}

async function savePermissions() {
  permsModal.saving = true;
  try {
    await userService.syncPermissions(permsModal.user.id, permsModal.selected);
    toastStore.success('Permissions updated successfully.');
    permsModal.open = false;
    fetchUsers(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to update permissions.');
  } finally {
    permsModal.saving = false;
  }
}

function confirmDelete(user) {
  deleteModal.user = user;
  deleteModal.open = true;
}

async function deleteUser() {
  deleteModal.deleting = true;
  try {
    await userService.remove(deleteModal.user.id);
    toastStore.success('User deleted successfully.');
    deleteModal.open = false;
    fetchUsers(pagination.current_page);
  } catch (e) {
    toastStore.error(e.response?.data?.message ?? 'Failed to delete user.');
  } finally {
    deleteModal.deleting = false;
  }
}

onMounted(async () => {
  await fetchUsers();
  try {
    const [rolesRes, permsRes] = await Promise.all([roleService.list(), permissionService.list()]);
    allRoles.value = rolesRes.data.data ?? rolesRes.data;
    allPermissions.value = permsRes.data.data ?? permsRes.data;
    roleOptions.value = [
      { value: '', label: 'All roles' },
      ...allRoles.value.map((r) => ({ value: r.name, label: r.name })),
    ];
  } catch {
    // non-critical
  }
});
</script>
