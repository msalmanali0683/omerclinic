export function normalizeVitals(vitals = null) {
  return {
    blood_pressure: vitals?.blood_pressure || '120/80',
    temperature: vitals?.temperature || '98.6',
    weight: vitals?.weight || 'N/A',
    pulse_rate: vitals?.pulse_rate || 80,
    respiratory_rate: vitals?.respiratory_rate || 18,
    recorded_at: vitals?.recorded_at || null,
    recorded_by: vitals?.recorded_by || null,
    is_default: !vitals,
  };
}
