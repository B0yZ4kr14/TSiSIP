# Feature 011 Implementation Plan

## Phase 1: Database Schema
- Add `force_password_change` column to `ocp_users`
- Update seed data to set flag for default Admin

## Phase 2: Authentication Layer
- Update `authenticateUser()` to return `force_password_change`
- Add `checkPasswordChange()` guard
- Update `login.php` to redirect forced-change users

## Phase 3: Passphrase Change Page
- Create `change-password.php` with validation logic
- Enforce 12-character minimum + complexity rules
- Update hash and clear flag on success

## Phase 4: Session Security
- Create `php-session-security.ini`
- Update Dockerfile to copy security config
- Add HTTPS detection via `X-Forwarded-Proto` in `config.php`

## Phase 5: Navigation Updates
- Add Account section to sidebar (`role-nav.php`)
- Add `checkPasswordChange()` to all protected pages

## Phase 6: Nginx & Documentation
- Verify `X-Forwarded-Proto` forwarding in nginx configs
- Update operator runbook with security guidance

## Phase 7: Deploy & Validate
- Build and push OCP Docker image
- Update database schema on VPS
- Deploy new image
- Run acceptance criteria validation
