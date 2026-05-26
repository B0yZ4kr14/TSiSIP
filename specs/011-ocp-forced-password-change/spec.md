# Feature 011: OCP Forced Passphrase Change & Session Security

**Status**: Completed

## Overview

Enhance the TSiSIP Control Panel (OCP) security posture by enforcing a mandatory passphrase change on first login and hardening PHP session cookies against XSS, CSRF, and session fixation attacks.

## Motivation

The default admin account is deployed with a predictable passphrase. Without a forced-change mechanism, operators may forget to rotate credentials, leaving the administrative interface exposed to brute-force attacks. Additionally, session cookies lacked Secure, HttpOnly, and SameSite protections, creating vectors for cookie theft and cross-site request forgery.

## Goals

1. **Forced Passphrase Change**: Any account with `force_password_change = TRUE` must set a strong passphrase before accessing any other OCP page.
2. **Strong Passphrase Policy**: Minimum 12 characters, uppercase, lowercase, number, and symbol.
3. **Session Cookie Hardening**: Secure, HttpOnly, SameSite=Strict, and use_strict_mode on all PHP sessions.
4. **HTTPS Detection Behind Proxy**: Dynamically enable session.cookie_secure based on X-Forwarded-Proto header from Nginx.

## Non-Goals

- Multi-factor authentication (MFA) — out of scope for this feature.
- Passphrase expiration policy (e.g., 90-day rotation) — out of scope.
- Email-based passphrase reset — out of scope.

## Security Requirements

| Requirement | Implementation |
|---|---|
| R1 | `force_password_change` column on `ocp_users` (BOOLEAN, DEFAULT TRUE) |
| R2 | `checkPasswordChange()` guard called after `requireAuth()` on all protected pages |
| R3 | `change-password.php` validates current passphrase and enforces complexity rules |
| R4 | On successful change, clear `force_password_change` flag and redirect to dashboard |
| R5 | PHP session cookies use HttpOnly, SameSite=Strict, Secure (when HTTPS detected) |
| R6 | `session.use_strict_mode = 1` to prevent session fixation |
| R7 | Nginx must forward X-Forwarded-Proto so PHP can detect HTTPS |

## Database Schema Changes

```sql
ALTER TABLE ocp_users
    ADD COLUMN IF NOT EXISTS force_password_change BOOLEAN NOT NULL DEFAULT TRUE;
```

## Files Changed

| File | Change |
|---|---|
| `db/init/02-tsisip-extensions.sql` | Add `force_password_change` column to `ocp_users` |
| `db/init/03-seed-data.sql` | Set `force_password_change = TRUE` for default Admin |
| `web/common/config.php` | Add `checkPasswordChange()`, HTTPS detection, return `force_password_change` from `authenticateUser()` |
| `web/login.php` | Store `ocp_force_password_change` in session; redirect to `change-password.php` if true |
| `web/change-password.php` | New page: validate current passphrase, enforce complexity, update hash, clear flag |
| `web/dashboard.php` | Add `checkPasswordChange()` after `requireAuth()` |
| `web/dispatcher.php` | Add `checkPasswordChange()` after `requireAuth()` |
| `web/rtpengine.php` | Add `checkPasswordChange()` after `requireAuth()` |
| `web/wiki.php` | Add `checkPasswordChange()` after `requireAuth()` |
| `web/common/role-nav.php` | Add Account section with "Change Passphrase" and "Sign Out" links |
| `docker/ocp/php-session-security.ini` | New file: session cookie hardening directives |
| `docker/ocp/Dockerfile` | Copy `php-session-security.ini` into PHP conf.d |
| `deploy/nginx/tsisip-reverse-proxy.conf` | Ensure X-Forwarded-Proto forwarding |
| `deploy/nginx/tsisip-location.conf` | Ensure X-Forwarded-Proto forwarding |
| `docs/TSiSIP-OPERATOR-RUNBOOK.md` | Document forced passphrase change, requirements, session security |

## Acceptance Criteria

- [x] AC1: Login with default admin redirects to `change-password.php`
- [x] AC2: Attempting to access `dashboard.php` without changing passphrase redirects back to `change-password.php`
- [x] AC3: `change-password.php` rejects passphrases shorter than 12 characters
- [x] AC4: `change-password.php` rejects passphrases missing complexity requirements
- [x] AC5: `change-password.php` rejects incorrect current passphrase
- [x] AC6: After successful passphrase change, user is redirected to `dashboard.php`
- [x] AC7: Subsequent login with new passphrase goes directly to `dashboard.php`
- [x] AC8: Session cookie `PHPSESSID` has Secure, HttpOnly, and SameSite=Strict flags
- [x] AC9: `session.use_strict_mode` is enabled in PHP configuration
- [x] AC10: All changes are deployed and validated on the VPS

## References

- `docs/TSiSIP-OPERATOR-RUNBOOK.md` — OCP Access, Admin Passphrase Management, Session Cookie Security
- `docs/TSiSIP-CANONICAL-SPEC.md` — Section 19.1 (OCP Authentication)
- Feature 010: `specs/010-ocp-navigation-system-links/`

## User Scenarios & Testing

### Scenario 1: Primary happy-path flow
- **Given** the feature is enabled and all dependencies are healthy
- **When** an authorized user performs the canonical action
- **Then** the system responds correctly and produces the expected outcome

### Scenario 2: Error or edge-case handling
- **Given** the feature is enabled
- **When** an invalid input or failure condition occurs
- **Then** the system fails gracefully with a clear error and no data corruption

### Scenario 3: Administrative or operational flow
- **Given** an operator with appropriate role permissions
- **When** the operator inspects or modifies configuration
- **Then** the change is persisted, auditable, and reflected in runtime behavior


## Requirements

### Functional Requirements

#### FR-011-001: Core Capability
**Description**: The system shall provide the primary capability described in this feature specification.
**Acceptance Criteria**:
- The capability is available when the feature is enabled.
- The capability integrates with existing TSiSIP components (OpenSIPS, PostgreSQL, OCP) without regression.

#### FR-011-002: Configuration & Persistence
**Description**: All configuration changes shall be persisted to PostgreSQL and reflected in runtime behavior without requiring a full stack restart.
**Acceptance Criteria**:
- Configuration changes survive container restarts.
- Invalid configuration is rejected at the validation gate.

#### FR-011-003: Observability & Audit
**Description**: The feature shall emit metrics or audit events compatible with the TSiSIP Prometheus/Grafana and OCP audit logging pipelines.
**Acceptance Criteria**:
- Metrics or audit events are visible in the appropriate dashboard or log.
- Failure conditions are logged with sufficient context for debugging.


## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-011-001 | Feature functional completeness | End-to-end validation test pass rate | 100% |
| SC-011-002 | Configuration persistence | Restart test with prior configuration | Pass |
| SC-011-003 | Zero regression in existing flows | Existing integration tests pass rate | 100% |
| SC-011-004 | Observability coverage | Metrics/audit events present | 100% of mutating actions |

