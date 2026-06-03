<template>
  <div :id="printAreaId" class="laboratory-test-bill-print-area lab-bill-a4-document bg-white text-black">
    <header class="lab-bill-letterhead">
      <p v-if="printData.hospital_name" class="lab-bill-hospital">{{ printData.hospital_name }}</p>
      <h1 class="lab-bill-title">{{ printData.title || 'Laboratory Test Bill' }}</h1>
      <div class="lab-bill-meta">
        <div>Bill #: <strong>{{ printData.bill?.bill_no || '—' }}</strong></div>
        <div>Date: {{ printData.bill?.created_at || printData.generated_at || '—' }}</div>
        <div v-if="printData.printed_by">Prepared by: {{ printData.printed_by }}</div>
      </div>
    </header>

    <section class="lab-bill-patient-panel">
      <div class="lab-bill-patient-title">Patient Details</div>
      <div class="lab-bill-patient-grid">
        <div><strong>Patient:</strong> {{ printData.patient?.patient_name || '—' }}</div>
        <div><strong>Father:</strong> {{ printData.patient?.patient_father_name || '—' }}</div>
        <div><strong>MR#:</strong> {{ printData.patient?.mr_number || '—' }}</div>
        <div><strong>Cell:</strong> {{ printData.patient?.patient_cell || '—' }}</div>
        <div><strong>Visit:</strong> {{ printData.visit_label || 'Not Linked / No Visit' }}</div>
        <div v-if="printData.visit?.doctor_name"><strong>Doctor:</strong> {{ printData.visit.doctor_name }}</div>
      </div>
    </section>

    <table class="lab-bill-tests-table">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th>Test Name</th>
          <th class="col-price">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(test, index) in printData.tests" :key="index">
          <td class="col-num">{{ index + 1 }}</td>
          <td>{{ test.test_name || '—' }}</td>
          <td class="col-price">{{ formatCurrency(test.test_price) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="lab-bill-totals">
      <div class="lab-bill-totals-row">
        <span>Subtotal</span>
        <span>{{ formatCurrency(printData.bill?.subtotal) }}</span>
      </div>
      <div v-if="Number(printData.bill?.discount) > 0" class="lab-bill-totals-row">
        <span>Discount</span>
        <span>{{ formatCurrency(printData.bill?.discount) }}</span>
      </div>
      <div class="lab-bill-totals-row">
        <span>Grand Total</span>
        <span>{{ formatCurrency(printData.bill?.total) }}</span>
      </div>
    </div>

    <footer class="lab-bill-footer">
      Laboratory test bill — results pending until entered and verified.
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { formatCurrency } from '@/utils/formatters';
import { ensureBillPreviewStyles } from '@/utils/laboratoryBillPrintStyles';

defineProps({
  printData: { type: Object, required: true },
  printAreaId: { type: String, default: 'laboratory-test-bill-print-area' },
});

onMounted(() => {
  ensureBillPreviewStyles();
});
</script>
