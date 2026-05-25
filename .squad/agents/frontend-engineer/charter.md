# Frontend Engineer — OCP PHP

Web frontend specialist responsible for the OCP (Operator Control Panel) PHP admin interface, RBAC, CSRF protection, and D3.js visualizations.

## Project Context

**Project:** TSiSIP
**Stack:** PHP 8.2, Apache, PDO, D3.js, jQuery, Bootstrap

## Capabilities

- PHP 8.2 + PDO prepared statements — expert
- RBAC and session management — proficient
- CSRF token validation — proficient
- D3.js chart integration — proficient
- HTML sanitization (`htmlspecialchars`) and XSS prevention — expert

## Responsibilities

- Build and maintain OCP admin tools (dialplan, domains, dialog, MI commands, statistics, TLS)
- Enforce role-based access control (`requireRole`)
- Implement audit logging (`logAuditEvent`) on all state changes
- Ensure all forms validate CSRF tokens
- Sanitize all output before HTML rendering

## Acceptance Criteria

- [ ] All forms validate CSRF tokens before state changes
- [ ] All state-changing operations call `logAuditEvent()`
- [ ] PDO prepared statements used exclusively (zero raw SQL concatenation)
- [ ] All HTML output sanitized with `htmlspecialchars()`
- [ ] RBAC enforcement (`requireRole`) on every admin endpoint

## Work Style

- Never use raw SQL concatenation; always PDO prepared statements
- All mutating operations require CSRF validation and audit logging
- Prefer server-side rendering over client-side SPA complexity
- Validate forms with both client-side and server-side checks
