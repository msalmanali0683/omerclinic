<template>
  <div class="space-y-4">
    <BaseInput
      v-model="search"
      placeholder="Search permissions..."
      class="mb-2"
    />
    <div
      v-for="(perms, module) in filteredGroups"
      :key="module"
      class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-900/50">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ module }}</h4>
        <div class="flex gap-2">
          <button type="button" class="text-xs text-teal-600 hover:underline" @click="selectModule(module)">Select all</button>
          <button type="button" class="text-xs text-gray-500 hover:underline" @click="clearModule(module)">Clear</button>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-4">
        <label
          v-for="perm in perms"
          :key="perm.id ?? perm.name"
          class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer"
        >
          <input
            type="checkbox"
            :value="perm.name"
            :checked="modelValue.includes(perm.name)"
            class="rounded border-gray-300 text-teal-600 focus:ring-teal-500"
            @change="toggle(perm.name)"
          />
          <span>{{ perm.name }}</span>
        </label>
      </div>
    </div>
    <p v-if="!Object.keys(filteredGroups).length" class="text-sm text-gray-500 text-center py-4">
      No permissions match your search.
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import { groupPermissions } from '@/utils/menu';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const search = ref('');

const grouped = computed(() => groupPermissions(props.permissions));

const filteredGroups = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return grouped.value;

  const result = {};
  for (const [module, perms] of Object.entries(grouped.value)) {
    const filtered = perms.filter((p) => p.name.toLowerCase().includes(q));
    if (filtered.length) result[module] = filtered;
  }
  return result;
});

function toggle(name) {
  const next = props.modelValue.includes(name)
    ? props.modelValue.filter((p) => p !== name)
    : [...props.modelValue, name];
  emit('update:modelValue', next);
}

function selectModule(module) {
  const names = filteredGroups.value[module]?.map((p) => p.name) ?? [];
  emit('update:modelValue', [...new Set([...props.modelValue, ...names])]);
}

function clearModule(module) {
  const names = new Set(filteredGroups.value[module]?.map((p) => p.name) ?? []);
  emit('update:modelValue', props.modelValue.filter((p) => !names.has(p)));
}
</script>
