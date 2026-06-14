<template>
  <div class="scan-rich-text-field">
    <div v-if="!disabled" class="scan-rich-text-field__toolbar">
      <button
        type="button"
        class="scan-rich-text-field__bold-btn"
        :disabled="disabled"
        title="Bold selected text"
        @click="applyBold"
      >
        <span class="scan-rich-text-field__bold-icon">B</span>
        Bold
      </button>
      <span class="scan-rich-text-field__hint">{{ boldHint }}</span>
    </div>

    <textarea
      v-if="multiline"
      ref="controlRef"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      rows="3"
      class="clinical-scan-field__control clinical-scan-field__textarea"
      @input="onInput"
      @select="rememberSelection"
      @keyup="rememberSelection"
      @mouseup="rememberSelection"
      @blur="rememberSelection"
    />

    <input
      v-else
      ref="controlRef"
      :value="modelValue"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      class="clinical-scan-field__control"
      @input="onInput"
      @select="rememberSelection"
      @keyup="rememberSelection"
      @mouseup="rememberSelection"
      @blur="rememberSelection"
    />

    <div
      v-if="showPreview"
      class="scan-rich-text-field__preview bidi-text"
      v-html="previewHtml"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue';
import {
  SCAN_FIELD_BOLD_HINT,
  renderScanFieldRichHtml,
  scanFieldHasBoldMarkup,
  wrapScanFieldBoldSelection,
} from '@/utils/scanFieldRichText';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  multiline: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const boldHint = SCAN_FIELD_BOLD_HINT;
const controlRef = ref(null);
const selectionStart = ref(0);
const selectionEnd = ref(0);

const showPreview = computed(() => scanFieldHasBoldMarkup(props.modelValue));
const previewHtml = computed(() => renderScanFieldRichHtml(props.modelValue));

function rememberSelection(event) {
  selectionStart.value = event.target.selectionStart ?? 0;
  selectionEnd.value = event.target.selectionEnd ?? selectionStart.value;
}

function onInput(event) {
  rememberSelection(event);
  emit('update:modelValue', event.target.value);
}

async function applyBold() {
  const result = wrapScanFieldBoldSelection(
    props.modelValue,
    selectionStart.value,
    selectionEnd.value,
  );

  emit('update:modelValue', result.value);

  await nextTick();

  const control = controlRef.value;

  if (!control) {
    return;
  }

  control.focus();
  control.setSelectionRange(result.selectionStart, result.selectionEnd);
  selectionStart.value = result.selectionStart;
  selectionEnd.value = result.selectionEnd;
}
</script>

<style scoped>
.scan-rich-text-field {
  width: 100%;
}

.scan-rich-text-field__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.55rem;
  padding: 0.35rem 0.45rem;
  border-radius: 0.55rem;
  border: 1px solid var(--scan-toolbar-border, #cbd5e1);
  background: var(--scan-toolbar-bg, #ffffff);
}

.scan-rich-text-field__bold-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.6rem;
  border-radius: 0.45rem;
  border: 1px solid var(--scan-accent, #0f766e);
  background: var(--scan-accent, #0f766e);
  font-size: 0.75rem;
  font-weight: 700;
  color: #ffffff;
  cursor: pointer;
}

.scan-rich-text-field__bold-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.scan-rich-text-field__bold-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.1rem;
  height: 1.1rem;
  border-radius: 0.25rem;
  background: rgb(255 255 255 / 0.22);
  font-weight: 800;
}

.scan-rich-text-field__hint {
  font-size: 0.72rem;
  color: var(--scan-sub-label, #475569);
}

.scan-rich-text-field__preview {
  margin-top: 0.5rem;
  padding: 0.55rem 0.75rem;
  border-radius: 0.65rem;
  border: 1.5px dashed var(--scan-preview-border, #94a3b8);
  background: var(--scan-preview-bg, #ffffff);
  font-size: 0.8125rem;
  color: var(--scan-input-text, #0f172a);
  white-space: pre-wrap;
  box-shadow: inset 0 1px 2px rgb(15 23 42 / 0.04);
}

.scan-rich-text-field__preview :deep(strong) {
  font-weight: 700;
  color: var(--scan-accent, #0f766e);
}
</style>
