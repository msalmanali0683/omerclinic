import { formatPrintVitalValue, formatPrintVitals } from '../resources/js/utils/printVitalsFormat.js';

let passed = 0;
let failed = 0;

function assert(condition, label) {
    if (condition) {
        passed += 1;
        return;
    }

    failed += 1;
    console.error(`FAIL: ${label}`);
}

assert(formatPrintVitalValue('120/80', 'mmHg') === '120/80 mmHg', 'appends mmHg');
assert(formatPrintVitalValue('120/80 mmHg', 'mmHg') === '120/80 mmHg', 'skips duplicate mmHg');
assert(formatPrintVitalValue('98.6', '°F') === '98.6 °F', 'appends Fahrenheit');
assert(formatPrintVitalValue('80', '/Min') === '80 /Min', 'appends pulse unit');
assert(formatPrintVitalValue('70', 'Kg') === '70 Kg', 'appends weight unit');
assert(formatPrintVitalValue('N/A', 'mmHg') === 'N/A', 'N/A unchanged');

const formatted = formatPrintVitals({
    blood_pressure: '120/80',
    temperature: '98.6',
    weight: '70',
    pulse_rate: '80',
    respiratory_rate: '18',
});

assert(formatted.blood_pressure === '120/80 mmHg', 'formatted BP');
assert(formatted.temperature === '98.6 °F', 'formatted temp');
assert(formatted.weight === '70 Kg', 'formatted weight');
assert(formatted.pulse_rate === '80 /Min', 'formatted pulse');
assert(formatted.respiratory_rate === '18 /Min', 'formatted RR');

console.log(`printVitalsFormat tests: ${passed} passed, ${failed} failed`);
process.exit(failed > 0 ? 1 : 0);
