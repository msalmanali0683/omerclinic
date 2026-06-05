<template>
  <div class="space-y-3">
    <div v-if="description" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
      <span v-if="descriptionLabel" class="font-semibold">{{ descriptionLabel }}:</span>
      {{ description }}
    </div>

    <div v-if="imagePreviewUrl" class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900/40">
      <img
        :src="imagePreviewUrl"
        :alt="imageLabel || 'X-Ray Image'"
        class="w-full max-h-[32rem] object-contain cursor-zoom-in"
        @click="openViewer"
      />
      <p class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
        Click image to enlarge — scroll to zoom, drag to pan
      </p>
    </div>

    <Teleport to="body">
      <div
        v-if="showFullscreen"
        class="fixed inset-0 z-50 flex flex-col bg-black/90"
      >
        <div class="flex items-center justify-between gap-3 px-4 py-3 bg-black/60 border-b border-white/10 shrink-0">
          <p class="text-sm text-white/80 truncate">
            {{ imageLabel || 'X-Ray Image' }}
            <span class="text-white/50 ml-2">{{ Math.round(scale * 100) }}%</span>
          </p>
          <div class="flex items-center gap-1 shrink-0">
            <button
              type="button"
              class="xray-viewer-btn"
              title="Zoom out"
              :disabled="scale <= MIN_SCALE"
              @click="zoomOut"
            >
              −
            </button>
            <button
              type="button"
              class="xray-viewer-btn"
              title="Reset view"
              @click="resetView"
            >
              Reset
            </button>
            <button
              type="button"
              class="xray-viewer-btn"
              title="Zoom in"
              :disabled="scale >= MAX_SCALE"
              @click="zoomIn"
            >
              +
            </button>
            <button
              type="button"
              class="xray-viewer-btn xray-viewer-btn--close"
              title="Close (Esc)"
              @click="closeViewer"
            >
              &times;
            </button>
          </div>
        </div>

        <div
          ref="viewportRef"
          class="flex-1 overflow-hidden relative select-none"
          :class="viewportCursor"
          @wheel.prevent="handleWheel"
          @mousedown="startPan"
          @mousemove="movePan"
          @mouseup="endPan"
          @mouseleave="endPan"
          @touchstart.passive="startTouch"
          @touchmove.prevent="moveTouch"
          @touchend="endTouch"
          @click.self="closeViewer"
        >
          <div
            class="absolute inset-0 flex items-center justify-center"
            :style="transformStyle"
          >
            <img
              ref="imageRef"
              :src="imagePreviewUrl"
              :alt="imageLabel || 'X-Ray Image'"
              class="max-w-none origin-center pointer-events-none"
              :style="imageSizeStyle"
              draggable="false"
              @load="onImageLoad"
            />
          </div>
        </div>

        <p class="shrink-0 px-4 py-2 text-xs text-center text-white/50 border-t border-white/10">
          Scroll to zoom · Drag to pan · Arrow keys to move · Esc to close
        </p>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  values: { type: Array, default: () => [] },
});

const MIN_SCALE = 1;
const MAX_SCALE = 6;
const ZOOM_STEP = 0.2;
const PAN_STEP = 40;

const showFullscreen = ref(false);
const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);
const isPanning = ref(false);
const panStartX = ref(0);
const panStartY = ref(0);
const panOriginX = ref(0);
const panOriginY = ref(0);
const viewportRef = ref(null);
const imageRef = ref(null);
const imageNaturalWidth = ref(0);
const imageNaturalHeight = ref(0);
const lastTouchDistance = ref(null);

const sortedValues = computed(() =>
  [...(props.values || [])].sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
);

const descriptionField = computed(() =>
  sortedValues.value.find((v) => v.field_type === 'textarea') ?? null
);

const imageField = computed(() =>
  sortedValues.value.find((v) => v.field_type === 'image') ?? null
);

const description = computed(() => descriptionField.value?.field_value || '');
const descriptionLabel = computed(() => descriptionField.value?.field_label || 'Description');
const imagePreviewUrl = computed(() => imageField.value?.preview_url || '');
const imageLabel = computed(() => imageField.value?.field_label || 'X-Ray Image');

const transformStyle = computed(() => ({
  transform: `translate(${translateX.value}px, ${translateY.value}px) scale(${scale.value})`,
  transition: isPanning.value ? 'none' : 'transform 0.12s ease-out',
}));

const imageSizeStyle = computed(() => {
  if (!imageNaturalWidth.value || !viewportRef.value) {
    return { maxHeight: '90vh', maxWidth: '90vw' };
  }

  const viewport = viewportRef.value.getBoundingClientRect();
  const fitScale = Math.min(
    (viewport.width * 0.9) / imageNaturalWidth.value,
    (viewport.height * 0.9) / imageNaturalHeight.value,
    1
  );

  return {
    width: `${imageNaturalWidth.value * fitScale}px`,
    height: `${imageNaturalHeight.value * fitScale}px`,
  };
});

const viewportCursor = computed(() => {
  if (scale.value <= 1 && translateX.value === 0 && translateY.value === 0) {
    return 'cursor-zoom-in';
  }

  return isPanning.value ? 'cursor-grabbing' : 'cursor-grab';
});

function resetView() {
  scale.value = 1;
  translateX.value = 0;
  translateY.value = 0;
}

function openViewer() {
  resetView();
  showFullscreen.value = true;
  nextTick(() => {
    document.body.style.overflow = 'hidden';
  });
}

function closeViewer() {
  showFullscreen.value = false;
  endPan();
  document.body.style.overflow = '';
}

function zoomIn() {
  scale.value = Math.min(MAX_SCALE, +(scale.value + ZOOM_STEP).toFixed(2));
}

function zoomOut() {
  const next = Math.max(MIN_SCALE, +(scale.value - ZOOM_STEP).toFixed(2));
  scale.value = next;

  if (next === MIN_SCALE) {
    translateX.value = 0;
    translateY.value = 0;
  }
}

function handleWheel(event) {
  const delta = event.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP;
  const next = Math.min(MAX_SCALE, Math.max(MIN_SCALE, +(scale.value + delta).toFixed(2)));
  scale.value = next;

  if (next === MIN_SCALE) {
    translateX.value = 0;
    translateY.value = 0;
  }
}

function startPan(event) {
  if (event.button !== 0) return;

  isPanning.value = true;
  panStartX.value = event.clientX;
  panStartY.value = event.clientY;
  panOriginX.value = translateX.value;
  panOriginY.value = translateY.value;
}

function movePan(event) {
  if (!isPanning.value) return;

  translateX.value = panOriginX.value + (event.clientX - panStartX.value);
  translateY.value = panOriginY.value + (event.clientY - panStartY.value);
}

function endPan() {
  isPanning.value = false;
}

function touchDistance(touches) {
  const dx = touches[0].clientX - touches[1].clientX;
  const dy = touches[0].clientY - touches[1].clientY;
  return Math.hypot(dx, dy);
}

function startTouch(event) {
  if (event.touches.length === 1) {
    isPanning.value = true;
    panStartX.value = event.touches[0].clientX;
    panStartY.value = event.touches[0].clientY;
    panOriginX.value = translateX.value;
    panOriginY.value = translateY.value;
    lastTouchDistance.value = null;
    return;
  }

  if (event.touches.length === 2) {
    isPanning.value = false;
    lastTouchDistance.value = touchDistance(event.touches);
  }
}

function moveTouch(event) {
  if (event.touches.length === 1 && isPanning.value) {
    translateX.value = panOriginX.value + (event.touches[0].clientX - panStartX.value);
    translateY.value = panOriginY.value + (event.touches[0].clientY - panStartY.value);
    return;
  }

  if (event.touches.length === 2 && lastTouchDistance.value) {
    const distance = touchDistance(event.touches);
    const delta = (distance - lastTouchDistance.value) * 0.005;
    scale.value = Math.min(MAX_SCALE, Math.max(MIN_SCALE, +(scale.value + delta).toFixed(2)));
    lastTouchDistance.value = distance;
  }
}

function endTouch() {
  isPanning.value = false;
  lastTouchDistance.value = null;
}

function handleKeydown(event) {
  if (!showFullscreen.value) return;

  if (event.key === 'Escape') {
    closeViewer();
    return;
  }

  if (event.key === '+' || event.key === '=') {
    event.preventDefault();
    zoomIn();
    return;
  }

  if (event.key === '-') {
    event.preventDefault();
    zoomOut();
    return;
  }

  if (event.key === '0') {
    event.preventDefault();
    resetView();
    return;
  }

  const panKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'];
  if (!panKeys.includes(event.key)) return;

  event.preventDefault();

  if (event.key === 'ArrowUp') translateY.value += PAN_STEP;
  if (event.key === 'ArrowDown') translateY.value -= PAN_STEP;
  if (event.key === 'ArrowLeft') translateX.value += PAN_STEP;
  if (event.key === 'ArrowRight') translateX.value -= PAN_STEP;
}

function onImageLoad(event) {
  imageNaturalWidth.value = event.target.naturalWidth;
  imageNaturalHeight.value = event.target.naturalHeight;
}

watch(showFullscreen, (open) => {
  if (open) {
    window.addEventListener('keydown', handleKeydown);
  } else {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.xray-viewer-btn {
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.5rem;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: background 0.15s;
}

.xray-viewer-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.22);
}

.xray-viewer-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.xray-viewer-btn--close {
  min-width: 2.25rem;
  font-size: 1.35rem;
  line-height: 1;
}
</style>
