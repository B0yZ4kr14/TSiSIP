# Blueprint — OCP Forced Passphrase Change & Session Security

## Overview

Enhance the TSiSIP Control Panel (OCP) security posture by enforcing a mandatory passphrase change on first login and hardening PHP session cookies against XSS, CSRF, and session fixation attacks.

## Requirements

1. **Forced Passphrase Change**: Any account with `force_password_change = TRUE` must set a strong passphrase before accessing any other OCP page.
2. **Strong Passphrase Policy**: Minimum 12 characters, uppercase, lowercase, number, and symbol.
3. **Session Cookie Hardening**: Secure, HttpOnly, SameSite=Strict, and `use_strict_mode` on all PHP sessions.
4. **HTTPS Detection Behind Proxy**: Dynamically enable `session.cookie_secure` based on `X-Forwarded-Proto` header from Nginx.

## Architecture

- **Stack**: PHP 8.2 + Apache (OCP container), PostgreSQL 16, Nginx reverse proxy.
- **Auth Flow**: `authenticateUser()` returns `force_password_change` flag → `login.php` stores in session → `checkPasswordChange()` guard on all protected pages → redirect to `change-password.php`.
- **Session Security**: `docker/ocp/php-session-security.ini` loaded into PHP `conf.d`; `config.php` detects HTTPS via `X-Forwarded-Proto`.
- **Database**: `ALTER TABLE ocp_users ADD COLUMN force_password_change BOOLEAN NOT NULL DEFAULT TRUE`.

## Implementation Plan

### Phase 1: Database Schema
- Add `force_password_change` column to `ocp_users`.
- Update seed data to set flag for default Admin.

### Phase 2: Authentication Layer
- Update `authenticateUser()` to return `force_password_change`.
- Add `checkPasswordChange()` guard.
- Update `login.php` to redirect forced-change users.

### Phase 3: Passphrase Change Page
- Create `change-password.php` with validation logic.
- Enforce 12-character minimum + complexity rules.
- Update hash and clear flag on success.

### Phase 4: Session Security
- Create `php-session-security.ini`.
- Update Dockerfile to copy security config.
- Add HTTPS detection via `X-Forwarded-Proto` in `config.php`.

### Phase 5: Navigation Updates
- Add Account section to sidebar (`role-nav.php`).
- Add `checkPasswordChange()` to all protected pages.

### Phase 6: Nginx & Documentation
- Verify `X-Forwarded-Proto` forwarding in nginx configs.
- Update operator runbook with security guidance.

### Phase 7: Deploy & Validate
- Build and push OCP Docker image.
- Update database schema on VPS.
- Deploy new image.
- Run acceptance criteria validation.

## Tasks

- T1: Add `force_password_change` column to `02-tsisip-extensions.sql`
- T2: Update `03-seed-data.sql` to set flag for Admin
- T3: Update `authenticateUser()` in `config.php` to return flag
- T4: Add `checkPasswordChange()` guard in `config.php`
- T5: Update `login.php` to store flag and redirect
- T6: Create `change-password.php` with validation
- T7: Add `checkPasswordChange()` to `dashboard.php`
- T8: Add `checkPasswordChange()` to `dispatcher.php`
- T9: Add `checkPasswordChange()` to `rtpengine.php`
- T10: Add `checkPasswordChange()` to `wiki.php`
- T11: Update `role-nav.php` with Account section
- T12: Create `docker/ocp/php-session-security.ini`
- T13: Update `docker/ocp/Dockerfile` to copy security config
- T14: Add HTTPS detection via `X-Forwarded-Proto` in `config.php`
- T15: Update nginx configs to ensure `X-Forwarded-Proto` forwarding
- T16: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md`
- T17: Build and push OCP Docker image
- T18: Update database schema on VPS
- T19: Deploy new image on VPS
- T20: Validate acceptance criteria (login redirect, cookie flags, passphrase change)

## Validation

- AC1: Login with default admin redirects to `change-password.php`.
- AC2: Accessing `dashboard.php` without changing passphrase redirects back.
- AC3: `change-password.php` rejects passphrases shorter than 12 characters.
- AC4: Rejects passphrases missing complexity requirements.
- AC5: Rejects incorrect current passphrase.
- AC6: After successful change, user is redirected to `dashboard.php`.
- AC7: Subsequent login with new passphrase goes directly to `dashboard.php`.
- AC8: Session cookie `PHPSESSID` has Secure, HttpOnly, and SameSite=Strict flags.
- AC9: `session.use_strict_mode` is enabled in PHP configuration.
- AC10: All changes are deployed and validated on the VPS.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Operators bypass forced change via direct URL | `checkPasswordChange()` guard on every protected page |
| Weak passphrase policy accepted by browser autofill | Server-side validation only; no client-side bypass |
| Session fixation via crafted PHPSESSID | `session.use_strict_mode = 1` regenerates session ID on auth |
| HTTPS detection failure behind misconfigured proxy | Document Nginx `X-Forwarded-Proto` requirement |

**Dependencies**: OCP v9 PHP baseline; PostgreSQL; Nginx reverse proxy; Docker Compose.
