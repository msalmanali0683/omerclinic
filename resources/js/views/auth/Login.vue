<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-teal-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 items-center justify-center text-white text-2xl font-bold shadow-lg mb-4">H+</div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hospital Admin</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Sign in to your account</p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
        <form class="space-y-5" @submit.prevent="submit">
          <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            autocomplete="email"
            placeholder="admin@example.com"
            :error="errors.email"
            required
          />
          <BaseInput
            v-model="form.password"
            label="Password"
            type="password"
            autocomplete="current-password"
            show-password-toggle
            :error="errors.password"
            required
          />

          <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500" />
            Remember me
          </label>

          <div v-if="errors.general" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm">
            {{ errors.general }}
          </div>

          <p class="text-center text-sm">
            <router-link to="/lab-reports" class="text-teal-600 hover:text-teal-700 dark:text-teal-400">
              Print lab reports (patient)
            </router-link>
          </p>

          <BaseButton type="submit" class="w-full" :loading="loading">
            Sign In
          </BaseButton>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { getApiErrorMessage, isNetworkError } from '@/utils/apiErrors';
import { resolvePostLoginRedirect } from '@/utils/navigation';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({ email: '', password: '', remember: false });
const errors = reactive({ email: '', password: '', general: '' });
const loading = ref(false);

function clearErrors() {
  errors.email = '';
  errors.password = '';
  errors.general = '';
}

async function submit() {
  clearErrors();
  loading.value = true;
  try {
    await authStore.login(form);
    const target = resolvePostLoginRedirect(authStore.user, route.query.redirect, router);
    router.replace(target);
  } catch (e) {
    const data = e.response?.data;
    if (data?.errors) {
      errors.email = data.errors.email?.[0] ?? '';
      errors.password = data.errors.password?.[0] ?? '';
    } else if (e.response?.status === 419) {
      errors.general = 'Your session expired. Please try signing in again.';
    } else if (isNetworkError(e)) {
      errors.general = getApiErrorMessage(e, 'Login failed. Please check your internet connection.');
    } else {
      errors.general = getApiErrorMessage(e, 'Login failed. Please check your credentials.');
    }
  } finally {
    loading.value = false;
  }
}
</script>
