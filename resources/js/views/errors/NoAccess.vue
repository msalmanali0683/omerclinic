<template>
  <div class="min-h-[60vh] flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-6xl font-bold text-gray-300 dark:text-gray-600 mb-4">!</div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Access</h1>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        No accessible modules are assigned to your account. Please contact your administrator.
      </p>
      <div class="flex flex-wrap justify-center gap-3">
        <BaseButton variant="secondary" @click="goToProfile">View Profile</BaseButton>
        <BaseButton variant="ghost" @click="logout">Sign Out</BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import BaseButton from '@/components/ui/BaseButton.vue';

const router = useRouter();
const authStore = useAuthStore();

function goToProfile() {
  router.push({ name: 'profile' });
}

async function logout() {
  await authStore.logout();
  router.replace({ name: 'login' });
}
</script>
