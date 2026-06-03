import { laboratoryResultService } from '@/services/laboratoryResultService';
import { laboratoryTestTemplateService } from '@/services/laboratoryTestTemplateService';
import { buildResultValuesFromTemplate } from '@/utils/laboratory';

export async function fetchDraftTests(patientId, visitId = null) {
  if (!patientId) {
    return [];
  }

  const response = visitId
    ? await laboratoryResultService.getVisitTests(patientId, visitId)
    : await laboratoryResultService.getNoVisitTests(patientId);

  const tests = response.data?.tests ?? [];

  return tests.filter((test) => test.status === 'draft');
}

export async function loadDraftResultForm(resultId) {
  const { data } = await laboratoryResultService.getResult(resultId);
  const row = data.data ?? data;

  let templateFields = row.template?.fields ?? [];

  if (!templateFields.length && row.laboratory_test_template_id) {
    const templateRes = await laboratoryTestTemplateService.getTemplate(row.laboratory_test_template_id);
    const template = templateRes.data.data ?? templateRes.data;
    templateFields = template.fields ?? [];
  }

  return {
    result: row,
    resultValues: buildResultValuesFromTemplate(templateFields, row.values ?? []),
    form: {
      laboratory_test_template_id: String(row.laboratory_test_template_id ?? ''),
      test_price: row.test_price !== null && row.test_price !== undefined ? String(row.test_price) : '',
      remarks: row.remarks || '',
    },
  };
}
