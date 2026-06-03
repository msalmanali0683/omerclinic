<template>
  <div class="min-h-[60vh] flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-6xl font-bold text-gray-300 dark:text-gray-600 mb-4">403</div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Access Denied</h1>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        You do not have permission to access this page. If your role or permissions were recently updated,
        refresh your session and try again.
      </p>
      <div class="flex flex-wrap justify-center gap-3">
        <BaseButton :loading="refreshing" @click="refreshAndRetry">Refresh permissions</BaseButton>
        <BaseButton variant="secondary" @click="goHome">Go to allowed page</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { resolveDefaultRoute } from '@/utils/navigation';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();
const refreshing = ref(false);

async function refreshAndRetry() {
  refreshing.value = true;
  try {
    await authStore.refreshUser();
    router.replace(resolveDefaultRoute(authStore.user));
  } finally {
    refreshing.value = false;
  }
}

function goHome() {
  router.replace(resolveDefaultRoute(authStore.user));
}
</script>
