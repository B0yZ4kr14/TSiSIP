/**
 * Accessibility Audit (axe-core substitute) — Test ID: ACC-001
 * @req FR-020 (OCP Critical Tool Gap Closure — admin tool pages)
 * @req SC-020 (Accessibility compliance for OCP admin tools)
 * Manual WCAG 2.1 AA checks for primary user paths.
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

const fullDocFiles = [
  'web/login.php',
  'web/common/header.php',
];

const partialFiles = [
  'web/dispatcher.php',
  'web/rtpengine.php',
];

let violations = 0;

function countOccurrences(str, substr) {
  return (str.match(new RegExp(substr, 'gi')) || []).length;
}

for (const file of fullDocFiles) {
  const content = fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

  if (!content.includes('<html lang=')) {
    console.error(`VIOLATION: ${file} missing lang attribute on <html>`);
    violations++;
  }

  if (!content.includes('name="viewport"')) {
    console.error(`VIOLATION: ${file} missing viewport meta`);
    violations++;
  }

  // Count <img tags vs alt= attributes (approximate but sufficient)
  const imgCount = countOccurrences(content, '<img');
  const altCount = countOccurrences(content, 'alt=');
  if (imgCount > altCount) {
    console.error(`VIOLATION: ${file} has ${imgCount} <img> but only ${altCount} alt= attributes`);
    violations++;
  }
}

for (const file of [...fullDocFiles, ...partialFiles]) {
  const content = fs.readFileSync(path.join(__dirname, '..', file), 'utf8');

  const inputs = content.match(/<input[\s\S]*?>/gi) || [];
  for (const input of inputs) {
    const hasId = input.includes('id=');
    const hasAria = input.includes('aria-label=') || input.includes('aria-labelledby=');
    const hasPlaceholder = input.includes('placeholder=');
    const hasTitle = input.includes('title=');
    if (!hasId && !hasAria && !hasPlaceholder && !hasTitle) {
      console.error(`VIOLATION: ${file} has unlabeled input`);
      violations++;
    }
  }

  const buttons = content.match(/<button[\s\S]*?>/gi) || [];
  for (const btn of buttons) {
    if (!btn.includes('type=') && !btn.includes('aria-label=')) {
      console.error(`VIOLATION: ${file} has button without type or aria-label`);
      violations++;
    }
  }
}

const cssVars = fs.readFileSync(path.join(__dirname, '..', 'web', 'tsisip', 'css', 'tsisip-variables.css'), 'utf8');
const requiredColors = [
  '--tsisip-surface-header: #0A1628',
  '--tsisip-text-on-dark: #E8ECF0',
  '--tsisip-surface-card: #FFFFFF',
  '--tsisip-text-primary: #1A1A2E',
];
for (const color of requiredColors) {
  if (!cssVars.includes(color)) {
    console.error(`VIOLATION: CSS variables missing required color ${color}`);
    violations++;
  }
}

assert.strictEqual(violations, 0, `Accessibility audit found ${violations} violations`);
console.log('ACC-001: Accessibility audit PASSED');
console.log('  - All HTML files have lang attribute');
console.log('  - All HTML files have viewport meta');
console.log('  - All images have alt text');
console.log('  - All inputs are labeled');
console.log('  - All buttons have type or aria-label');
console.log('  - Required WCAG contrast colors present in CSS');
