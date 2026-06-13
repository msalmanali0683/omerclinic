import { createApp, nextTick } from 'vue';
import VisitPrintPreview from '@/components/prints/VisitPrintPreview.vue';
import LaboratoryResultPrintPreview from '@/components/laboratory/LaboratoryResultPrintPreview.vue';
import LaboratoryBillPrintPreview from '@/components/laboratory/LaboratoryBillPrintPreview.vue';
import PatientReportPrintPreview from '@/components/reports/PatientReportPrintPreview.vue';
import LaboratoryReportPrintPreview from '@/components/reports/LaboratoryReportPrintPreview.vue';
import PatientTokenPrintPreview from '@/components/tokens/PatientTokenPrintPreview.vue';
import { useAuthStore } from '@/stores/auth';
import { prescriptionPrintSettingsService } from '@/services/prescriptionPrintSettingsService';
import {
  applyPrescriptionPrintPageStyle,
  getDefaultResolvedSettings,
  getPrintElementOptions,
  mergePrescriptionPrintSettings,
} from '@/utils/prescriptionPrintSettings';
import { printPrescriptionElement } from '@/utils/printPrescription';
import { printLaboratoryReportElement } from '@/utils/printLaboratoryReport';
import { printLaboratoryBillElement } from '@/utils/printLaboratoryBill';
import { printPatientReportElement } from '@/utils/printPatientReport';
import { printPatientTokenElement } from '@/utils/printPatientToken';
import { ensureClinicalScanPrintStyles } from '@/utils/clinicalScanPrintStyles';
import { waitForBillQrCode } from '@/utils/generateQrCodeDataUrl';

let printCounter = 0;

function nextPrintAreaId(prefix) {
  printCounter += 1;
  return `${prefix}-${printCounter}-${Date.now()}`;
}

async function mountPrintComponent(Component, props) {
  const container = document.createElement('div');
  container.style.cssText = 'position:fixed;left:-99999px;top:0;opacity:0;pointer-events:none;overflow:hidden;';
  document.body.appendChild(container);

  const app = createApp(Component, props);
  const instance = app.mount(container);

  await nextTick();
  await new Promise((resolve) => {
    requestAnimationFrame(() => requestAnimationFrame(resolve));
  });

  return {
    instance,
    unmount() {
      app.unmount();
      container.remove();
    },
  };
}

async function resolvePrescriptionPrintSettings(printData) {
  let settings = mergePrescriptionPrintSettings(
    printData?.print_settings ?? getDefaultResolvedSettings(),
  );

  try {
    const authStore = useAuthStore();
    if (authStore.can('manage prescription print settings')) {
      const { data } = await prescriptionPrintSettingsService.getSettings();
      settings = mergePrescriptionPrintSettings(data.data ?? data);
    }
  } catch {
    // Keep payload/default settings when API load fails.
  }

  applyPrescriptionPrintPageStyle(settings);
  ensureClinicalScanPrintStyles(settings.font_size_clinical_scans);

  return settings;
}

export async function directPrintPrescription(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const printAreaId = options.printAreaId ?? nextPrintAreaId('prescription-print-area');
  const settings = await resolvePrescriptionPrintSettings(printData);
  const mount = await mountPrintComponent(VisitPrintPreview, {
    printData,
    printSettings: settings,
    showEmptyClinicalScansAsNa: options.showEmptyClinicalScansAsNa ?? true,
    printAreaId,
  });

  try {
    await printPrescriptionElement(printAreaId, getPrintElementOptions(settings), {
      onAfterPrint: options.onAfterPrint,
    });
  } finally {
    mount.unmount();
  }
}

export async function directPrintClinicalScan(printData, options = {}) {
  return directPrintPrescription(printData, {
    ...options,
    showEmptyClinicalScansAsNa: false,
  });
}

export async function directPrintLaboratoryReport(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const printAreaId = options.printAreaId ?? nextPrintAreaId('laboratory-report-print-area');
  const mount = await mountPrintComponent(LaboratoryResultPrintPreview, {
    printData,
    printAreaId,
  });

  try {
    await printLaboratoryReportElement(printAreaId, {
      onAfterPrint: options.onAfterPrint,
    });
  } finally {
    mount.unmount();
  }
}

export async function directPrintLaboratoryBill(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const printAreaId = options.printAreaId ?? nextPrintAreaId('laboratory-test-bill-print-area');
  const mount = await mountPrintComponent(LaboratoryBillPrintPreview, {
    printData,
    printAreaId,
  });

  try {
    await mount.instance?.waitForQrCode?.();
    await nextTick();
    await waitForBillQrCode(printAreaId);
    await printLaboratoryBillElement(printAreaId);
    options.onAfterPrint?.();
  } finally {
    mount.unmount();
  }
}

export async function directPrintPatientReport(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const printAreaId = options.printAreaId ?? nextPrintAreaId('patient-report-print-area');
  const mount = await mountPrintComponent(PatientReportPrintPreview, {
    printData,
    printAreaId,
  });

  try {
    await printPatientReportElement(printAreaId, {
      onAfterPrint: options.onAfterPrint,
    });
  } finally {
    mount.unmount();
  }
}

export async function directPrintLaboratoryBillingReport(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const printAreaId = options.printAreaId ?? nextPrintAreaId('laboratory-billing-report-print-area');
  const mount = await mountPrintComponent(LaboratoryReportPrintPreview, {
    printData,
    printAreaId,
  });

  try {
    await printLaboratoryReportElement(printAreaId, {
      onAfterPrint: options.onAfterPrint,
    });
  } finally {
    mount.unmount();
  }
}

export async function directPrintPatientToken(printData, options = {}) {
  if (!printData) {
    throw new Error('Print data is required.');
  }

  const mount = await mountPrintComponent(PatientTokenPrintPreview, {
    patientName: printData.patient_name,
    fatherName: printData.patient_father_name,
    mrNumber: printData.mr_number,
    tokenNumber: printData.token_number,
    tokenDisplay: printData.token_display,
    tokenDate: printData.token_date,
    visitDate: printData.visit_date,
  });

  try {
    await printPatientTokenElement('patient-token-print-area', {
      paperWidth: options.paperWidth ?? '80mm',
    }, {
      onAfterPrint: options.onAfterPrint,
    });
  } finally {
    mount.unmount();
  }
}
