<template>
  <VisitPrintPreview
    :print-data="printData"
    :show-empty-clinical-scans-as-na="true"
  />
</template>

<script setup>
import VisitPrintPreview from '@/components/prints/VisitPrintPreview.vue';

defineProps({
  printData: { type: Object, required: true },
});
</script>
