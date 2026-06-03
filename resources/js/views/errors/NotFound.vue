<template>
  <div class="min-h-[60vh] flex items-center justify-center p-4">
    <div class="text-center max-w-md">
      <div class="text-6xl font-bold text-gray-300 dark:text-gray-600 mb-4">404</div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Page Not Found</h1>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        The page you are looking for does not exist or has been moved.
      </p>
      <BaseButton @click="$router.push('/dashboard')">Back to Dashboard</BaseButton>
    </div>
  </div>
</template>

<script setup>
import BaseButton from '@/components/ui/BaseButton.vue';
</script>
