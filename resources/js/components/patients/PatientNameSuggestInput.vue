<template>
  <div class="relative">
    <textarea
      v-if="multiline"
      :value="modelValue"
      :rows="rows"
      :class="inputClass"
      :placeholder="placeholder"
      autocomplete="off"
      @input="onInput"
      @focus="openDropdownIfReady"
      @blur="closeDropdown"
      @keydown.escape="hideDropdown"
    />
    <input
      v-else
      :value="modelValue"
      type="text"
      :class="inputClass"
      :placeholder="placeholder"
      autocomplete="off"
      @input="onInput"
      @focus="openDropdownIfReady"
      @blur="closeDropdown"
      @keydown.enter.prevent="selectFirstSuggestion"
      @keydown.escape="hideDropdown"
    />

    <ul
      v-if="showDropdown && suggestions.length"
      class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border shadow-xl text-sm"
      :class="dropdownClass"
    >
      <li
        v-for="(option, index) in suggestions"
        :key="`${option.type}-${option.value}-${index}`"
        class="cursor-pointer border-b px-3 py-2 last:border-0"
        :class="dropdownItemClass"
        @mousedown.prevent="selectSuggestion(option)"
      >
        <span class="block font-medium text-gray-900 dark:text-white">{{ option.value }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { patientService } from '@/services/patientService';
import { SEARCH_DEBOUNCE_MS } from '@/composables/useAutoSearch';

const props = defineProps({
  modelValue: { type: String, default: '' },
  inputClass: { type: String, required: true },
  placeholder: { type: String, default: 'Enter name' },
  field: {
    type: String,
    default: 'patient_name',
    validator: (value) => ['patient_name', 'patient_father_name', 'patient_address'].includes(value),
  },
  multiline: { type: Boolean, default: false },
  rows: { type: [Number, String], default: 2 },
  dropdownClass: {
    type: String,
    default: 'border-emerald-200 bg-white dark:border-emerald-800 dark:bg-gray-900',
  },
  dropdownItemClass: {
    type: String,
    default: 'border-gray-100 hover:bg-emerald-50 dark:border-gray-800 dark:hover:bg-emerald-900/20',
  },
});

const emit = defineEmits(['update:modelValue']);

const authStore = useAuthStore();
const suggestions = ref([]);
const showDropdown = ref(false);
const searchTimer = ref(null);

const canSuggest = computed(() => authStore.can('search patients'));

function normalizeValue(value) {
  return String(value ?? '').replace(/\r?\n/g, ' ');
}

function parseInput(value) {
  const text = normalizeValue(value);
  const hasTrailingSpace = /\s$/.test(text);
  const parts = text.trim() ? text.trim().split(/\s+/u) : [];

  if (hasTrailingSpace) {
    return {
      completedWords: parts,
      partialWord: '',
    };
  }

  if (parts.length <= 1) {
    return {
      completedWords: [],
      partialWord: parts[0] ?? '',
    };
  }

  return {
    completedWords: parts.slice(0, -1),
    partialWord: parts[parts.length - 1] ?? '',
  };
}

function shouldFetch(value) {
  const text = normalizeValue(value).trim();

  if (!text) {
    return false;
  }

  if (!text.includes(' ')) {
    return text.length >= 1;
  }

  const { partialWord } = parseInput(value);

  return partialWord.length >= 1 || /\s$/.test(normalizeValue(value));
}

function buildValueFromWord(optionValue) {
  const { completedWords } = parseInput(props.modelValue);
  const prefix = completedWords.join(' ');

  return prefix ? `${prefix} ${optionValue}` : optionValue;
}

function onInput(event) {
  const value = event.target.value;
  emit('update:modelValue', value);
  queueSuggestions(value);
}

function queueSuggestions(value) {
  clearTimeout(searchTimer.value);

  if (!canSuggest.value || !shouldFetch(value)) {
    suggestions.value = [];
    showDropdown.value = false;

    return;
  }

  searchTimer.value = setTimeout(() => fetchSuggestions(value), SEARCH_DEBOUNCE_MS);
}

async function fetchSuggestions(value) {
  if (!canSuggest.value || !shouldFetch(value)) {
    return;
  }

  try {
    const { data } = await patientService.suggestPatientNames(normalizeValue(value), props.field);
    suggestions.value = data.data ?? [];
    showDropdown.value = suggestions.value.length > 0;
  } catch {
    suggestions.value = [];
    showDropdown.value = false;
  }
}

function openDropdownIfReady() {
  if (suggestions.value.length) {
    showDropdown.value = true;
    return;
  }

  queueSuggestions(props.modelValue);
}

function closeDropdown() {
  setTimeout(() => {
    showDropdown.value = false;
  }, 150);
}

function hideDropdown() {
  showDropdown.value = false;
}

function selectSuggestion(option) {
  const nextValue = option.type === 'word'
    ? buildValueFromWord(option.value ?? '')
    : (option.value ?? '');

  emit('update:modelValue', nextValue);
  suggestions.value = [];
  showDropdown.value = false;
}

function selectFirstSuggestion() {
  if (suggestions.value.length) {
    selectSuggestion(suggestions.value[0]);
  }
}
</script>
