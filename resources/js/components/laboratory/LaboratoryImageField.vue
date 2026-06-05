<template>
  <div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div
      ref="dropZoneRef"
      tabindex="0"
      class="relative rounded-xl border-2 border-dashed transition-colors outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
      :class="dropZoneClass"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      @paste="handlePaste"
      @click="openFilePicker"
    >
      <input
        ref="fileInputRef"
        type="file"
        accept="image/*"
        class="hidden"
        :disabled="disabled || uploading"
        @change="handleFileSelect"
      />

      <div v-if="previewUrl && !uploading" class="p-3">
        <div class="relative group">
          <img
            :src="previewUrl"
            :alt="label"
            class="max-h-80 w-full object-contain rounded-lg bg-gray-900/5 dark:bg-gray-900/40"
          />
          <div
            v-if="!disabled"
            class="absolute inset-0 flex items-center justify-center gap-2 rounded-lg bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-lg bg-white text-gray-900 hover:bg-gray-100"
              @click.stop="openFilePicker"
            >
              Replace
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-lg bg-teal-600 text-white hover:bg-teal-700"
              :disabled="pasteLoading"
              @click.stop="pasteFromClipboard"
            >
              {{ pasteLoading ? 'Pasting...' : 'Paste Clipboard' }}
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700"
              @click.stop="removeImage"
            >
              Remove
            </button>
          </div>
        </div>
        <p v-if="fileName" class="mt-2 text-xs text-gray-500 dark:text-gray-400 truncate">{{ fileName }}</p>
      </div>

      <div v-else-if="uploading" class="p-8 text-center">
        <div class="inline-block h-8 w-8 animate-spin rounded-full border-2 border-teal-600 border-t-transparent" />
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">Uploading image...</p>
      </div>

      <div v-else class="p-8 text-center cursor-pointer select-none">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">
          Click to choose, drag &amp; drop, or paste screenshot
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          JPEG, PNG, WebP up to 15 MB — or use Paste Clipboard if you copied a screenshot
        </p>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-teal-600 text-white hover:bg-teal-700 disabled:opacity-50"
            :disabled="disabled"
            @click.stop="openFilePicker"
          >
            Choose Image
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg border border-teal-600 text-teal-700 dark:text-teal-300 hover:bg-teal-50 dark:hover:bg-teal-900/30 disabled:opacity-50"
            :disabled="disabled || pasteLoading"
            @click.stop="pasteFromClipboard"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ pasteLoading ? 'Pasting...' : 'Paste Clipboard' }}
          </button>
        </div>
      </div>
    </div>

    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useToastStore } from '@/stores/toast';
import { laboratoryResultService } from '@/services/laboratoryResultService';

const toastStore = useToastStore();

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  resultId: { type: [Number, String], required: true },
  valueId: { type: [Number, String], default: null },
  label: { type: String, default: 'X-Ray Image' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  previewUrl: { type: String, default: '' },
  error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'uploaded', 'removed']);

const fileInputRef = ref(null);
const dropZoneRef = ref(null);
const isDragging = ref(false);
const uploading = ref(false);
const pasteLoading = ref(false);
const localPreviewUrl = ref('');
const fileName = ref('');

const previewUrl = computed(() => localPreviewUrl.value || props.previewUrl || '');

const dropZoneClass = computed(() => {
  if (props.disabled) {
    return 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 cursor-not-allowed';
  }

  if (isDragging.value) {
    return 'border-teal-500 bg-teal-50 dark:bg-teal-900/20 cursor-copy';
  }

  return 'border-gray-300 dark:border-gray-600 hover:border-teal-400 dark:hover:border-teal-500 cursor-pointer';
});

function revokeLocalPreview() {
  if (localPreviewUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(localPreviewUrl.value);
  }
}

function setLocalPreview(file) {
  revokeLocalPreview();
  localPreviewUrl.value = URL.createObjectURL(file);
  fileName.value = file.name;
}

async function uploadFile(file) {
  if (!file?.type?.startsWith('image/')) {
    return;
  }

  uploading.value = true;
  setLocalPreview(file);

  try {
    const formData = new FormData();
    formData.append('file', file);

    if (props.valueId) {
      formData.append('laboratory_result_value_id', String(props.valueId));
    }

    const { data } = await laboratoryResultService.uploadAttachment(props.resultId, formData);
    const attachment = data.data ?? data;

    if (props.modelValue && props.modelValue !== attachment.id) {
      try {
        await laboratoryResultService.deleteAttachment(props.resultId, props.modelValue);
      } catch {
        // Previous attachment may already be gone.
      }
    }

    emit('update:modelValue', String(attachment.id));
    emit('uploaded', attachment);
    localPreviewUrl.value = attachment.preview_url || localPreviewUrl.value;
  } catch (e) {
    revokeLocalPreview();
    localPreviewUrl.value = props.previewUrl || '';
    throw e;
  } finally {
    uploading.value = false;
  }
}

function openFilePicker() {
  if (props.disabled || uploading.value) return;
  fileInputRef.value?.click();
}

function handleFileSelect(event) {
  const file = event.target.files?.[0];
  if (file) {
    uploadFile(file).catch(() => {});
  }
  event.target.value = '';
}

function handleDrop(event) {
  isDragging.value = false;
  if (props.disabled || uploading.value) return;

  const file = event.dataTransfer?.files?.[0];
  if (file) {
    uploadFile(file).catch(() => {});
  }
}

function fileFromPasteEvent(items) {
  for (const item of items) {
    if (item.type?.startsWith('image/')) {
      const file = item.getAsFile();
      if (file) return file;
    }
  }

  return null;
}

async function fileFromClipboardApi() {
  if (!navigator.clipboard?.read) {
    return null;
  }

  const items = await navigator.clipboard.read();

  for (const item of items) {
    const imageType = item.types.find((type) => type.startsWith('image/'));

    if (!imageType) continue;

    const blob = await item.getType(imageType);
    const ext = imageType.split('/')[1]?.replace('jpeg', 'jpg') || 'png';

    return new File([blob], `clipboard.${ext}`, { type: imageType });
  }

  return null;
}

async function pasteFromClipboard() {
  if (props.disabled || uploading.value || pasteLoading.value) return;

  pasteLoading.value = true;

  try {
    const file = await fileFromClipboardApi();

    if (!file) {
      toastStore.error('No image in clipboard. Copy a screenshot first (e.g. Win+Shift+S), then click Paste Clipboard.');
      return;
    }

    await uploadFile(file);
    toastStore.success('Image pasted from clipboard.');
  } catch {
    dropZoneRef.value?.focus();
    toastStore.error('Could not read clipboard. Allow clipboard access, or focus the box and press Ctrl+V.');
  } finally {
    pasteLoading.value = false;
  }
}

function handlePaste(event) {
  if (props.disabled || uploading.value) return;

  const file = fileFromPasteEvent(event.clipboardData?.items ?? []);

  if (file) {
    event.preventDefault();
    uploadFile(file).catch(() => {});
  }
}

async function removeImage() {
  if (props.disabled || uploading.value) return;

  if (props.modelValue) {
    try {
      await laboratoryResultService.deleteAttachment(props.resultId, props.modelValue);
    } catch {
      // Ignore if already deleted.
    }
  }

  revokeLocalPreview();
  localPreviewUrl.value = '';
  fileName.value = '';
  emit('update:modelValue', '');
  emit('removed');
}

watch(
  () => props.previewUrl,
  (url) => {
    if (!localPreviewUrl.value?.startsWith('blob:')) {
      localPreviewUrl.value = url || '';
    }
  }
);

onBeforeUnmount(() => {
  revokeLocalPreview();
});
</script>
