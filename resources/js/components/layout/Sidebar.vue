<template>
  <nav class="px-3 py-4 space-y-1">
    <template v-for="item in visibleItems" :key="item.label">
      <div v-if="item.children?.length">
        <button
          type="button"
          class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50"
          @click="toggleGroup(item.label)"
        >
          <span class="flex items-center gap-3">
            <AppIcon :name="item.icon" />
            {{ item.label }}
          </span>
          <svg :class="['w-4 h-4 transition-transform', openGroups[item.label] ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div v-show="openGroups[item.label]" class="ml-4 mt-1 space-y-1 border-l border-gray-200 dark:border-gray-700 pl-3">
          <RouterLink
            v-for="child in item.children"
            :key="child.to"
            :to="child.to"
            class="block px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-teal-900/20 dark:hover:text-teal-300"
            active-class="bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300 font-medium"
            @click="$emit('navigate')"
          >
            {{ child.label }}
          </RouterLink>
        </div>
      </div>
      <RouterLink
        v-else
        :to="item.placeholder ? '/dashboard' : item.to"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50"
        active-class="bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300"
        @click="$emit('navigate')"
      >
        <AppIcon :name="item.icon" />
        {{ item.label }}
        <span v-if="item.placeholder" class="ml-auto text-[10px] uppercase tracking-wide text-gray-400">Soon</span>
      </RouterLink>
    </template>
  </nav>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { sidebarMenu } from '@/config/sidebarMenu';
import { filterMenuByPermissions } from '@/utils/permissions';
import AppIcon from '@/components/ui/AppIcon.vue';

defineEmits(['navigate']);

const authStore = useAuthStore();
const openGroups = reactive({});

const visibleItems = computed(() =>
  filterMenuByPermissions(sidebarMenu, authStore.user)
);

function toggleGroup(label) {
  openGroups[label] = !openGroups[label];
}

watch(
  visibleItems,
  (items) => {
    items.filter((item) => item.children?.length).forEach((item) => {
      if (openGroups[item.label] === undefined) {
        openGroups[item.label] = true;
      }
    });
  },
  { immediate: true }
);
</script>
