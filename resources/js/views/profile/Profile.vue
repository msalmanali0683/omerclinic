<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h2>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Manage your account settings</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm mb-6">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-16 h-16 rounded-full bg-teal-600 text-white flex items-center justify-center text-xl font-bold">
          {{ initials }}
        </div>
        <div>
          <p class="font-semibold text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
          <p class="text-sm text-gray-500">{{ authStore.user?.email }}</p>
          <div class="flex flex-wrap gap-1 mt-2">
            <span
              v-for="role in authStore.userRoles"
              :key="role"
              class="px-2 py-0.5 text-xs rounded-full bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 capitalize"
            >{{ role.replace(/-/g, ' ') }}</span>
          </div>
        </div>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <BaseInput
          v-model="form.name"
          label="Name"
          :error="errors.name"
          :disabled="!canEditIdentity"
          :hint="canEditIdentity ? '' : 'Only administrators can change name and email.'"
        />
        <BaseInput
          v-model="form.email"
          label="Email"
          type="email"
          :error="errors.email"
          :disabled="!canEditIdentity"
        />
        <BaseInput v-model="form.password" label="New Password" type="password" hint="Leave blank to keep current" :error="errors.password" />
        <BaseInput v-model="form.password_confirmation" label="Confirm Password" type="password" :error="errors.password_confirmation" />

        <div class="flex gap-3 pt-2">
          <BaseButton type="submit" :loading="saving">Update Profile</BaseButton>
          <BaseButton variant="danger" @click="logout">Logout</BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { authService } from '@/services/authService';
import { useFormErrors } from '@/composables/useFormErrors';
import { isHospitalAdmin } from '@/utils/permissions';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();
const { errors, setErrors, clearErrors } = useFormErrors();

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const saving = ref(false);

const initials = computed(() =>
  (authStore.user?.name ?? '?').split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase()
);

const canEditIdentity = computed(() => isHospitalAdmin(authStore.user));

watch(
  () => authStore.user,
  (user) => {
    if (user) {
      form.name = user.name;
      form.email = user.email;
    }
  },
  { immediate: true }
);

async function submit() {
  clearErrors();
  saving.value = true;
  const payload = {};
  if (canEditIdentity.value) {
    payload.name = form.name;
    payload.email = form.email;
  }
  if (form.password) {
    payload.password = form.password;
    payload.password_confirmation = form.password_confirmation;
  }
  try {
    const { data } = await authService.updateProfile(payload);
    authStore.user = data.user;
    form.password = '';
    form.password_confirmation = '';
    toastStore.success('Profile updated successfully.');
  } catch (e) {
    setErrors(e);
    toastStore.error(e.response?.data?.message ?? 'Failed to update profile.');
  } finally {
    saving.value = false;
  }
}

async function logout() {
  await authStore.logout();
  router.push('/login');
}
</script>
