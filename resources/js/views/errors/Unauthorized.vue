<template>
  <div class="min-h-[60vh] flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-6xl font-bold text-gray-300 dark:text-gray-600 mb-4">
        {{ networkStore.isOffline || authStore.networkOffline ? '!' : '403' }}
      </div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        {{ pageTitle }}
      </h1>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        {{ pageMessage }}
      </p>
      <div class="flex flex-wrap justify-center gap-3">
        <BaseButton :loading="refreshing" @click="refreshAndRetry">
          {{ networkStore.isOffline || authStore.networkOffline ? 'Retry connection' : 'Refresh permissions' }}
        </BaseButton>
        <BaseButton variant="secondary" @click="goHome">Go to allowed page</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNetworkStore } from '@/stores/network';
import { NETWORK_ERROR_MESSAGE } from '@/utils/apiErrors';
import { resolveDefaultRoute } from '@/utils/navigation';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();
const networkStore = useNetworkStore();
const refreshing = ref(false);

const pageTitle = computed(() => (
    networkStore.isOffline || authStore.networkOffline
        ? 'Network Error'
        : 'Access Denied'
));

const pageMessage = computed(() => (
    networkStore.isOffline || authStore.networkOffline
        ? `${NETWORK_ERROR_MESSAGE} You will be redirected automatically when the connection is restored.`
        : 'You do not have permission to access this page. If your role or permissions were recently updated, refresh your session and try again.'
));

async function refreshAndRetry() {
    refreshing.value = true;

    try {
        if (networkStore.isOffline || authStore.networkOffline) {
            const restored = await networkStore.recoverSession(router);

            if (restored) {
                return;
            }
        }

        await authStore.refreshUser();
        router.replace(resolveDefaultRoute(authStore.user));
    } finally {
        refreshing.value = false;
    }
}

function goHome() {
    router.replace(resolveDefaultRoute(authStore.user));
}

function handleOnline() {
    refreshAndRetry();
}

onMounted(() => {
    window.addEventListener('online', handleOnline);

    if (networkStore.isOffline || authStore.networkOffline) {
        refreshAndRetry();
    }
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
});
</script>
