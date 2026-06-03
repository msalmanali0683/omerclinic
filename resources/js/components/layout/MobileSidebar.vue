<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="modelValue" class="fixed inset-0 z-40 lg:hidden">
        <div class="fixed inset-0 bg-black/50" @click="$emit('update:modelValue', false)" />
        <aside class="fixed inset-y-0 left-0 w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col shadow-xl">
          <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-sm">H+</div>
              <span class="font-semibold text-gray-900 dark:text-white">Hospital Admin</span>
            </div>
            <button class="text-gray-500" @click="$emit('update:modelValue', false)">✕</button>
          </div>
          <div class="flex-1 overflow-y-auto">
            <Sidebar @navigate="$emit('update:modelValue', false)" />
          </div>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import Sidebar from '@/components/layout/Sidebar.vue';

defineProps({ modelValue: { type: Boolean, default: false } });
defineEmits(['update:modelValue']);
</script>

<style scoped>
.drawer-enter-active, .drawer-leave-active { transition: opacity 0.2s; }
.drawer-enter-from, .drawer-leave-to { opacity: 0; }
</style>
