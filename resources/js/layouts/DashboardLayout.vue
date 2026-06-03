<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
    <aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 fixed inset-y-0 left-0 z-20">
      <div class="flex items-center gap-2 h-16 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-white font-bold">H+</div>
        <div>
          <p class="font-semibold text-gray-900 dark:text-white leading-tight">Hospital Admin</p>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Management System</p>
        </div>
      </div>
      <div class="flex-1 overflow-y-auto">
        <Sidebar />
      </div>
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ authStore.user?.email }}</p>
        <p class="text-xs text-teal-600 dark:text-teal-400 capitalize">{{ primaryRole }}</p>
      </div>
    </aside>

    <MobileSidebar v-model="sidebarOpen" />

    <div class="flex-1 lg:pl-64 flex flex-col min-h-screen">
      <Header @toggle-sidebar="sidebarOpen = true" />
      <main class="flex-1 p-4 lg:p-6">
        <Breadcrumb />
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import Sidebar from '@/components/layout/Sidebar.vue';
import MobileSidebar from '@/components/layout/MobileSidebar.vue';
import Header from '@/components/layout/Header.vue';
import Breadcrumb from '@/components/layout/Breadcrumb.vue';

const authStore = useAuthStore();
const sidebarOpen = ref(false);
const primaryRole = computed(() => authStore.userRoles[0]?.replace(/-/g, ' ') ?? 'user');
</script>
