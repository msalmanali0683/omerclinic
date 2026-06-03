<template>
  <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4">
    <RouterLink to="/dashboard" class="hover:text-teal-600 dark:hover:text-teal-400">Home</RouterLink>
    <template v-for="(crumb, i) in crumbs" :key="i">
      <span class="mx-2">/</span>
      <RouterLink
        v-if="crumb.to"
        :to="crumb.to"
        class="hover:text-teal-600 dark:hover:text-teal-400"
      >{{ crumb.label }}</RouterLink>
      <span v-else class="text-gray-900 dark:text-gray-200 font-medium">{{ crumb.label }}</span>
    </template>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const crumbs = computed(() => {
  const items = [];
  if (route.meta.title && route.name !== 'dashboard') {
    items.push({ label: route.meta.title });
  }
  return items;
});
</script>
