<template>
  <RouterView v-slot="{ Component, route }">
    <component :is="resolveLayout(route)">
      <component :is="Component" />
    </component>
  </RouterView>
  <Toast />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import { refreshCsrfCookie } from '@/utils/csrf';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import Toast from '@/components/ui/Toast.vue';

const authStore = useAuthStore();
const themeStore = useThemeStore();

function resolveLayout(route) {
  if (route.meta.layout === 'dashboard' && authStore.isAuthenticated) {
    return DashboardLayout;
  }
  return AuthLayout;
}

onMounted(async () => {
  themeStore.init();
  await refreshCsrfCookie();
  if (!authStore.initialized) {
    await authStore.fetchUser();
  }
});
</script>
