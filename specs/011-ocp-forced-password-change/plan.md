# Feature 011 Implementation Plan

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/011-ocp-forced-password-change/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



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
