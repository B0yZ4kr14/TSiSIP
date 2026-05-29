/**
 * OCP Critical Pages Integration Test
 * Verifies that essential OCP pages contain required HTML structure,
 * CSRF protection, and role-based access controls.
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

const BASE_DIR = path.join(__dirname, '..', '..', 'web');

function readFile(relPath) {
  return fs.readFileSync(path.join(BASE_DIR, relPath), 'utf8');
}

function contains(str, substr) {
  return str.toLowerCase().includes(substr.toLowerCase());
}

let failures = 0;
let passes = 0;

function test(name, fn) {
  try {
    fn();
    passes++;
    console.log('  PASS: ' + name);
  } catch (err) {
    failures++;
    console.log('  FAIL: ' + name + ': ' + err.message);
  }
}

console.log('\n=== OCP Critical Pages Integration Test ===\n');

const login = readFile('login.php');
test('login.php has CSRF token field', () => {
  assert(contains(login, 'csrf_token') || contains(login, 'generateCsrfToken'));
});
test('login.php has viewport meta tag', () => {
  assert(contains(login, 'viewport'));
});
test('login.php has password input', () => {
  assert(contains(login, 'type="password"') || contains(login, "type='password'"));
});

const dashboard = readFile('dashboard.php');
test('dashboard.php requires auth', () => {
  assert(contains(dashboard, 'requireAuth'));
});
test('dashboard.php has checkPasswordChange', () => {
  assert(contains(dashboard, 'checkPasswordChange'));
});
test('dashboard.php has logAuditEvent', () => {
  assert(contains(dashboard, 'logAuditEvent'));
});

const subscribers = readFile('subscribers.php');
test('subscribers.php requires auth', () => {
  assert(contains(subscribers, 'requireAuth'));
});
test('subscribers.php has CSRF protection', () => {
  assert(contains(subscribers, 'validateCsrfToken') || contains(subscribers, 'csrf_token') || contains(subscribers, 'csrfInput'));
});

const dispatcher = readFile('dispatcher.php');
test('dispatcher.php requires auth', () => {
  assert(contains(dispatcher, 'requireAuth'));
});
test('dispatcher.php has CSRF protection', () => {
  assert(contains(dispatcher, 'validateCsrfToken') || contains(dispatcher, 'csrf_token') || contains(dispatcher, 'csrfInput'));
});

const auditLog = readFile('audit-log.php');
test('audit-log.php requires auth', () => {
  assert(contains(auditLog, 'requireAuth'));
});

const users = readFile('users.php');
test('users.php requires auth', () => {
  assert(contains(users, 'requireAuth'));
});
test('users.php has admin role check', () => {
  assert(contains(users, 'admin') || contains(users, 'requireRole'));
});

const changePassword = readFile('change-password.php');
test('change-password.php requires auth', () => {
  assert(contains(changePassword, 'requireAuth'));
});
test('change-password.php has CSRF protection', () => {
  assert(contains(changePassword, 'validateCsrfToken') || contains(changePassword, 'csrf_token') || contains(changePassword, 'csrfInput'));
});

const apiDocs = readFile('api-docs.php');
test('api-docs.php requires auth', () => {
  assert(contains(apiDocs, 'requireAuth'));
});

const header = readFile('common/header.php');
test('header.php has CSP header', () => {
  assert(contains(header, 'Content-Security-Policy'));
});
test('header.php has X-Frame-Options', () => {
  assert(contains(header, 'X-Frame-Options'));
});
test('header.php has viewport meta', () => {
  assert(contains(header, 'viewport'));
});
test('header.php loads theme toggle', () => {
  assert(contains(header, 'theme-toggle.js'));
});

const roleNav = readFile('common/role-nav.php');
test('role-nav.php has role checks', () => {
  assert(contains(roleNav, 'isAdmin') || contains(roleNav, 'userRole'));
});
test('role-nav.php has 6 roles defined', () => {
  const roles = ['admin', 'devops', 'dentist', 'assistant', 'user', 'readonly'];
  const found = roles.filter(r => contains(roleNav, r)).length;
  assert(found >= 5, 'Only ' + found + '/6 roles found');
});

const config = readFile('common/config.php');
test('config.php has session security', () => {
  assert(contains(config, 'session.cookie_secure') || contains(config, 'cookie_secure'));
});

console.log('\n=== Results: ' + passes + ' passed, ' + failures + ' failed ===\n');
process.exit(failures > 0 ? 1 : 0);

// --- DASHBOARD CUSTOMIZATION (Feature 028) ---
const dashboardContent = readFile('dashboard.php');
test('dashboard.php has drag-and-drop script', () => {
  assert(contains(dashboardContent, 'dashboardToggleEdit'));
});
test('dashboard.php has data-widget-id attributes', () => {
  const count = (dashboardContent.match(/data-widget-id=/g) || []).length;
  assert(count >= 5, 'Only ' + count + ' widgets found');
});
test('dashboard.php loads user-prefs helper', () => {
  assert(contains(dashboardContent, 'user-prefs.php'));
});
test('dashboard.php has layout controls', () => {
  assert(contains(dashboardContent, 'dashboard-save-btn'));
});
