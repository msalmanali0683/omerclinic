<template>
  <BaseModal
    :model-value="networkStore.showModal"
    title="Network Error"
    size="sm"
    @update:model-value="onModalChange"
  >
    <p class="text-sm text-gray-600 dark:text-gray-300">
      {{ NETWORK_ERROR_MESSAGE }}
    </p>
    <p v-if="networkStore.isOffline" class="mt-2 text-xs text-amber-700 dark:text-amber-300">
      Waiting for internet connection…
    </p>

    <template #footer>
      <BaseButton variant="secondary" @click="networkStore.closeModal">Close</BaseButton>
      <BaseButton :loading="retrying" @click="retryNow">Retry</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useNetworkStore } from '@/stores/network';
import { NETWORK_ERROR_MESSAGE } from '@/utils/apiErrors';
import BaseModal from '@/components/ui/BaseModal.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const networkStore = useNetworkStore();
const retrying = ref(false);

function onModalChange(open) {
    if (!open) {
        networkStore.closeModal();
    }
}

async function retryNow() {
    retrying.value = true;

    try {
        await networkStore.recoverSession(router);
    } finally {
        retrying.value = false;
    }
}

function handleOnline() {
    retryNow();
}

onMounted(() => {
    window.addEventListener('online', handleOnline);
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
});
</script>
