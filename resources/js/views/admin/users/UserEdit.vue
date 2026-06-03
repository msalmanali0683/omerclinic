<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit User</h2>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ form.email }}</p>
    </div>

    <div v-if="pageLoading" class="animate-pulse h-64 bg-gray-200 dark:bg-gray-700 rounded-xl" />

    <form v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-5" @submit.prevent="submit">
      <BaseInput v-model="form.name" label="Name" :error="errors.name" required />
      <BaseInput v-model="form.email" label="Email" type="email" :error="errors.email" required />
      <BaseInput v-model="form.password" label="New Password" type="password" hint="Leave blank to keep current password" :error="errors.password" />
      <BaseInput v-model="form.password_confirmation" label="Confirm New Password" type="password" :error="errors.password_confirmation" />

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          <label v-for="role in roles" :key="role.name" class="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              :value="role.name"
              :checked="form.roles.includes(role.name)"
              class="rounded border-gray-300 text-teal-600"
              @change="toggleRole(role.name)"
            />
            {{ role.name }}
          </label>
        </div>
      </div>

      <div v-if="authStore.can('assign permissions')">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Direct Permissions</label>
        <PermissionPicker v-model="form.permissions" :permissions="permissions" />
      </div>

      <div class="flex gap-3 pt-2">
        <BaseButton type="submit" :loading="saving">Save Changes</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { userService } from '@/services/userService';
import { roleService } from '@/services/roleService';
import { permissionService } from '@/services/permissionService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import PermissionPicker from '@/components/admin/PermissionPicker.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [],
  permissions: [],
});

const roles = ref([]);
const permissions = ref([]);
const saving = ref(false);
const pageLoading = ref(true);

function toggleRole(name) {
  if (form.roles.includes(name)) {
    form.roles = form.roles.filter((r) => r !== name);
  } else {
    form.roles.push(name);
  }
}

async function submit() {
  clearErrors();
  saving.value = true;
  const payload = { ...form };
  if (!payload.password) {
    delete payload.password;
    delete payload.password_confirmation;
  }
  if (!authStore.can('assign permissions')) {
    delete payload.permissions;
  }
  try {
    const { data } = await userService.update(route.params.id, payload);
    const updated = data.user ?? data.data ?? data;
    if (String(updated?.id) === String(authStore.user?.id)) {
      authStore.setUser(updated);
    }
    toastStore.success('User updated successfully.');
    router.push('/admin/users');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to update user.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const [userRes, rolesRes, permsRes] = await Promise.all([
      userService.get(route.params.id),
      roleService.list(),
      permissionService.list(),
    ]);
    const user = userRes.data.data ?? userRes.data;
    form.name = user.name;
    form.email = user.email;
    form.roles = [...(user.roles ?? [])];
    form.permissions = [...(user.direct_permissions ?? [])];
    roles.value = rolesRes.data.data ?? rolesRes.data;
    permissions.value = permsRes.data.data ?? permsRes.data;
  } catch (e) {
    toastStore.error('Failed to load user.');
    router.push('/admin/users');
  } finally {
    pageLoading.value = false;
  }
});
</script>
