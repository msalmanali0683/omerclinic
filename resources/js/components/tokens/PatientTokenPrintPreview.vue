<template>
  <div id="patient-token-print-area" class="token-print-area">
    <h1 class="token-print-title">TOKEN</h1>
    <p class="token-print-number">Token No: {{ tokenDisplay }}</p>
    <p class="token-print-row">
      <span class="token-print-label">Patient Name:</span>
      {{ patientName || '—' }}
    </p>
    <p class="token-print-row">
      <span class="token-print-label">S/o, W/o, D/o:</span>
      {{ fatherName || '—' }}
    </p>
    <p class="token-print-row">
      <span class="token-print-label">MR No:</span>
      {{ mrNumber || '—' }}
    </p>
    <p v-if="formattedDate" class="token-print-row">
      <span class="token-print-label">Date:</span>
      {{ formattedDate }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate } from '@/utils/formatters';

const props = defineProps({
  patientName: { type: String, default: '' },
  fatherName: { type: String, default: '' },
  mrNumber: { type: String, default: '' },
  tokenNumber: { type: [Number, String], default: '' },
  tokenDisplay: { type: String, default: '' },
  tokenDate: { type: String, default: '' },
  visitDate: { type: String, default: '' },
});

const formattedDate = computed(() => formatDate(props.tokenDate || props.visitDate, ''));
const tokenDisplay = computed(() => props.tokenDisplay || String(props.tokenNumber ?? ''));
</script>

<style scoped>
.token-print-area {
  width: 80mm;
  max-width: 100%;
  padding: 4mm;
  box-sizing: border-box;
  font-family: Arial, sans-serif;
  color: #000;
  background: #fff;
}

.token-print-title {
  text-align: center;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin: 0 0 8px;
}

.token-print-number {
  text-align: center;
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 10px;
}

.token-print-row {
  margin: 0 0 4px;
  font-size: 13px;
}

.token-print-label {
  font-weight: 700;
}
</style>
