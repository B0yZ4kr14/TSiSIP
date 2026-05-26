# Feature 011 Tasks
## Phase 1: Implementation




- [x] T001: Add `force_password_change` column to `02-tsisip-extensions.sql`
- [x] T002: Update `03-seed-data.sql` to set flag for Admin
- [x] T003: Update `authenticateUser()` in `config.php` to return flag
- [x] T004: Add `checkPasswordChange()` guard in `config.php`
- [x] T005: Update `login.php` to store flag and redirect
- [x] T006: Create `change-password.php` with validation
- [x] T007: Add `checkPasswordChange()` to `dashboard.php`
- [x] T008: Add `checkPasswordChange()` to `dispatcher.php`
- [x] T009: Add `checkPasswordChange()` to `rtpengine.php`
- [x] T010: Add `checkPasswordChange()` to `wiki.php`
- [x] T011: Update `role-nav.php` with Account section
- [x] T012: Create `docker/ocp/php-session-security.ini`
- [x] T013: Update `docker/ocp/Dockerfile` to copy security config
- [x] T014: Add HTTPS detection via X-Forwarded-Proto in `config.php`
- [x] T015: Update nginx configs to ensure X-Forwarded-Proto forwarding
- [x] T016: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md`
- [x] T017: Build and push OCP Docker image
- [x] T018: Update database schema on VPS
- [x] T019: Deploy new image on VPS
- [x] T020: Validate acceptance criteria (login redirect, cookie flags, passphrase change)
