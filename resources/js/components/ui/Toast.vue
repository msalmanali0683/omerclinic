<template>
  <div class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border text-sm max-w-sm',
          toastClass(toast.type),
        ]"
      >
        <span class="flex-1">{{ toast.message }}</span>
        <button class="opacity-60 hover:opacity-100" @click="toastStore.remove(toast.id)">✕</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { storeToRefs } from 'pinia';
import { useToastStore } from '@/stores/toast';

const toastStore = useToastStore();
const { toasts } = storeToRefs(toastStore);

function toastClass(type) {
  if (type === 'success') {
    return 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-200';
  }

  if (type === 'warning') {
    return 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-200';
  }

  if (type === 'info') {
    return 'bg-sky-50 border-sky-200 text-sky-900 dark:bg-sky-900/30 dark:border-sky-700 dark:text-sky-200';
  }

  return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200';
}
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(100%); }
</style>
