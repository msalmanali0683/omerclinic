<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Create User</h2>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Add a new user to the system</p>
    </div>

    <form class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm space-y-5" @submit.prevent="submit">
      <BaseInput v-model="form.name" label="Name" :error="errors.name" required />
      <BaseInput v-model="form.email" label="Email" type="email" :error="errors.email" required />
      <BaseInput v-model="form.password" label="Password" type="password" :error="errors.password" required />
      <BaseInput v-model="form.password_confirmation" label="Confirm Password" type="password" :error="errors.password_confirmation" required />

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Role</label>
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
        <p v-if="errors.roles" class="mt-1 text-sm text-red-600">{{ errors.roles }}</p>
      </div>

      <div v-if="authStore.can('assign permissions')">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Direct Permissions (optional)</label>
        <PermissionPicker v-model="form.permissions" :permissions="permissions" />
      </div>

      <div class="flex gap-3 pt-2">
        <BaseButton type="submit" :loading="saving">Create User</BaseButton>
        <BaseButton variant="secondary" @click="$router.back()">Cancel</BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { userService } from '@/services/userService';
import { roleService } from '@/services/roleService';
import { permissionService } from '@/services/permissionService';
import { useFormErrors } from '@/composables/useFormErrors';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import PermissionPicker from '@/components/admin/PermissionPicker.vue';

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
  try {
    await userService.create(form);
    toastStore.success('User created successfully.');
    router.push('/admin/users');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to create user.');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  const [rolesRes, permsRes] = await Promise.all([roleService.list(), permissionService.list()]);
  roles.value = rolesRes.data.data ?? rolesRes.data;
  permissions.value = permsRes.data.data ?? permsRes.data;
});
</script>
