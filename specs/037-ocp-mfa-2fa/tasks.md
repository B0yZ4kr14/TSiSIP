# Tasks: Multi-Factor Authentication (MFA) for OCP (Feature 037)

## Phase 1: Database Schema & TOTP Core

- [x] T1.1: Create `ocp_user_mfa` table migration
- [x] T1.2: Create `ocp_user_backup_codes` table migration
- [x] T1.3: Create `mfa_policy` table migration with defaults
- [x] T1.4: Implement `web/lib/totp.php` — secret generation, verification, window check
- [x] T1.5: Implement `web/lib/crypto.php` — AES-256-GCM encrypt/decrypt with auth secret
- [x] T1.6: Implement QR code SVG generation in `web/lib/totp.php`
- [x] T1.7: Unit test: TOTP verification with known vectors
- [x] T1.8: Unit test: encryption roundtrip

## Phase 2: Login Flow Integration

- [x] T2.1: Modify `login.php` to check MFA status after password validation
- [x] T2.2: Create `web/mfa-verify.php` — TOTP input screen
- [x] T2.3: Create `web/mfa-backup.php` — backup code recovery screen
- [x] T2.4: Implement session regeneration after MFA verification
- [x] T2.5: Implement rate limiting (5 attempts / 15 min) on verify endpoint
- [x] T2.6: Implement account lockout after 3 failed MFA attempts
- [x] T2.7: Integration test: login with MFA enabled
- [x] T2.8: Integration test: backup code recovery

## Phase 3: Enrollment & Self-Service

- [x] T3.1: Add MFA section to `profile.php`
- [x] T3.2: Create `web/api/v1/mfa-enroll.php` — generate secret, QR code, verify first code
- [x] T3.3: Create `web/api/v1/mfa-disable.php` — require password + TOTP
- [x] T3.4: Create `web/api/v1/mfa-regenerate-backup.php`
- [x] T3.5: Display backup codes during enrollment (one-time only)
- [x] T3.6: Integration test: self-service enrollment
- [x] T3.7: Integration test: self-service disable

## Phase 4: Admin & Policy

- [x] T4.1: Add MFA status column to `users.php`
- [x] T4.2: Create web/api/v1/mfa-reset.php` — admin reset MFA for user
- [x] T4.3: Create web/api/v1/mfa-policy.php` — admin manage policy
- [x] T4.4: Implement policy enforcement redirect on login
- [x] T4.5: Integration test: admin reset MFA
- [x] T4.6: Integration test: policy enforcement

## Phase 5: Audit & Testing

- [x] T5.1: Add `logAuditEvent()` calls for all MFA events
- [x] T5.2: Integration test: rate limiting blocks brute force
- [x] T5.3: Integration test: replay protection (same code rejected)
- [x] T5.4: Integration test: no plaintext secrets in DB
- [x] T5.5: End-to-end test with Google Authenticator app simulation

## Phase 6: Deploy & Documentation

- [x] T6.1: Update TSiSIP-OPERATOR-RUNBOOK.md` with MFA procedures
- [x] T6.2: Deploy to VPS
- [x] T6.3: Final validation on VPS
- [x] T6.4: Merge to main, tag release
