<template>
  <header class="sticky top-0 z-30 h-16 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between h-full px-4 lg:px-6">
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
          @click="$emit('toggle-sidebar')"
        >
          <AppIcon name="menu" />
        </button>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white hidden sm:block">
          {{ pageTitle }}
        </h1>
      </div>

      <div class="flex items-center gap-2 sm:gap-4">
        <button
          type="button"
          class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
          @click="themeStore.toggle()"
        >
          <AppIcon :name="themeStore.dark ? 'sun' : 'moon'" />
        </button>

        <button
          type="button"
          class="relative p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
          title="Notifications"
        >
          <AppIcon name="bell" />
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full" />
        </button>

        <div ref="dropdownRef" class="relative">
          <button
            type="button"
            class="flex items-center gap-2 p-1.5 pr-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="open = !open"
          >
            <div class="w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center text-sm font-semibold">
              {{ initials }}
            </div>
            <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-200">{{ authStore.user?.name }}</span>
          </button>
          <div
            v-if="open"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1"
          >
            <RouterLink
              to="/profile"
              class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              @click="open = false"
            >Profile</RouterLink>
            <button
              type="button"
              class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700"
              @click="logout"
            >Logout</button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import AppIcon from '@/components/ui/AppIcon.vue';

defineEmits(['toggle-sidebar']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const themeStore = useThemeStore();
const open = ref(false);
const dropdownRef = ref(null);

const pageTitle = computed(() => route.meta.title ?? 'Dashboard');
const initials = computed(() => {
  const name = authStore.user?.name ?? '?';
  return name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
});

async function logout() {
  open.value = false;
  await authStore.logout();
  router.push('/login');
}

function onClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) open.value = false;
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>
