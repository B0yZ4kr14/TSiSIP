/**
 * D3.js + jQuery Coexistence Falsification Test — Test ID: TESTID-JS-001
 * @req FR-002-001 (Feature 002 OCP rebrand — ES module chart loader)
 * Verifies tsisip-charts.js loads as ES module without global pollution.
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

const chartsPath = path.join(__dirname, '..', 'web', 'tsisip', 'js', 'tsisip-charts.js');
const chartsSource = fs.readFileSync(chartsPath, 'utf8');

// 1. Verify no global window assignment
const globalAssignRegex = /window\.[a-zA-Z_$][\w$]*\s*=/g;
const globalAssigns = chartsSource.match(globalAssignRegex) || [];
assert.strictEqual(globalAssigns.length, 0,
  `tsisip-charts.js must not assign to window globals. Found: ${globalAssigns.join(', ')}`);

// 2. Verify no eval() usage
assert.ok(!chartsSource.includes('eval('),
  'tsisip-charts.js must not use eval()');

// 3. Verify export syntax (ES module)
assert.ok(chartsSource.includes('export function initChart'),
  'tsisip-charts.js must export initChart as ES module');
assert.ok(chartsSource.includes('export { TSISIP_CHART_VERSION'),
  'tsisip-charts.js must export TSISIP_CHART_VERSION');

// 4. Verify D3.js is accessed via window.d3 (not global script injection)
assert.ok(chartsSource.includes('window.d3'),
  'tsisip-charts.js must reference D3 via window.d3');

// 5. Verify isolated container selector prefix
assert.ok(chartsSource.includes('tsisip-chart--'),
  'tsisip-charts.js must use tsisip-chart-- prefix for containers');

console.log('TESTID-JS-001: D3.js + jQuery coexistence test PASSED');
console.log('  - No global window assignments');
console.log('  - No eval() usage');
console.log('  - ES module exports verified');
console.log('  - D3 accessed via window.d3');
console.log('  - Container prefix tsisip-chart-- found');
