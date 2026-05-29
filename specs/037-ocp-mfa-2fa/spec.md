# Feature Specification: Multi-Factor Authentication (MFA) for OCP

## Overview

**Feature**: Multi-Factor Authentication (MFA) for OCP  
**Short name**: ocp-mfa-2fa  
**Feature Number**: 037  
**Created**: 2026-05-28  
**Status**: Draft

### Context

TSiSIP OCP currently relies solely on username/password authentication. As a production-grade SIP infrastructure management platform handling sensitive tenant data, routing rules, and TLS certificates, single-factor authentication is insufficient for administrator and devops accounts. Regulatory frameworks (LGPD, GDPR) and security best practices require strong authentication for privileged access.

### Objective

Implement TOTP-based Multi-Factor Authentication (MFA) for the TSiSIP OCP with the following goals:
1. Allow users to self-enroll MFA via QR code (compatible with Google Authenticator, Authy, Microsoft Authenticator).
2. Require TOTP verification during login after password validation.
3. Provide backup/recovery codes for account recovery when TOTP device is lost.
4. Allow admins to enforce MFA policy: mandatory for admin/devops, optional for other roles.
5. Log all MFA events (enrollment, verification, backup code use, disable) to the audit trail.
6. Graceful degradation: allow admin bypass with recovery codes if TOTP server is unreachable.

---

## User Scenarios & Testing

### Primary Flows

#### Scenario 1: User Enrollment
- **Given** an authenticated user without MFA
- **When** they navigate to Profile → Security → Enable 2FA
- **Then** the system generates a TOTP secret and displays a QR code
- **And** the user scans the QR code with their authenticator app
- **And** enters a 6-digit TOTP code to verify enrollment
- **And** receives 10 backup codes for recovery

#### Scenario 2: Login with MFA
- **Given** a user with MFA enabled
- **When** they enter valid username and password
- **Then** they are redirected to the MFA verification screen
- **And** after entering a valid TOTP code, they are fully authenticated
- **And** after 3 failed attempts, the account is temporarily locked for 15 minutes

#### Scenario 3: Recovery with Backup Code
- **Given** a user with MFA enabled who lost their authenticator device
- **When** they click "Use backup code" on the MFA screen
- **And** enter a valid unused backup code
- **Then** they are authenticated
- **And** the backup code is marked as used
- **And** an alert is shown: "This backup code has been consumed. Please generate new ones."

#### Scenario 4: Admin Enforcement
- **Given** the admin sets MFA policy to "mandatory for admin and devops"
- **When** an admin or devops user logs in without MFA enrolled
- **Then** they are forced to the MFA enrollment page before accessing the dashboard
- **And** a banner is shown: "MFA is required for your role. Please enable it now."

### Edge Cases & Error Conditions

- **Edge case 1**: User enters wrong TOTP code — show error, allow retry, count toward lockout.
- **Edge case 2**: User exhausts all backup codes — require admin intervention to reset MFA.
- **Edge case 3**: Clock skew > 30 seconds — accept codes within ±1 window (RFC 6238).
- **Edge case 4**: Admin disables MFA for a user — require re-authentication and audit log.
- **Edge case 5**: TOTP secret compromised — user can regenerate, invalidating old secret and backup codes.

---

## Functional Requirements

### FR-037-001: TOTP Secret Generation & QR Code
**Description**: Generate RFC 6238 compliant TOTP secrets and display QR codes for enrollment.
**Acceptance Criteria**:
- Secrets are 32-byte base32 encoded strings.
- QR codes contain `otpauth://` URI with issuer "TSiSIP" and user email.
- QR codes are rendered as SVG (no external image dependencies).
- Secret is stored encrypted in the database (AES-256-GCM with app-level key).

### FR-037-002: TOTP Verification
**Description**: Validate 6-digit TOTP codes during login.
**Acceptance Criteria**:
- Accept codes within ±1 time window (30-second periods) to handle clock skew.
- Reject codes older than 1 window (prevent replay).
- Rate limit: max 5 attempts per 15 minutes per user.
- Store last used timestamp to prevent immediate code reuse.

### FR-037-003: Backup/Recovery Codes
**Description**: Generate and manage single-use backup codes.
**Acceptance Criteria**:
- Generate 10 codes per enrollment, 12 characters each (alphanumeric, uppercase).
- Codes are hashed with bcrypt before storage (never plaintext).
- Each code can be used only once; marked as consumed after use.
- Display codes only once during enrollment; allow regeneration.

### FR-037-004: MFA Policy & Enforcement
**Description**: Configurable MFA requirement per user role.
**Acceptance Criteria**:
- New table `mfa_policy` with columns: `role`, `enforced` (boolean), `grace_period_days`.
- Default: admin/devops = enforced, others = optional.
- During login, if role is enforced and MFA not enrolled, redirect to enrollment page.
- Grace period: new users have N days to enroll before being blocked.

### FR-037-005: Self-Service MFA Management
**Description**: Users can manage their own MFA settings from the profile page.
**Acceptance Criteria**:
- Enable MFA: show QR code, verify first code, display backup codes.
- Disable MFA: require password re-authentication and current TOTP code.
- Regenerate backup codes: invalidate old codes, generate new set.
- Regenerate TOTP secret: invalidate old secret and all backup codes.

### FR-037-006: Admin MFA Management
**Description**: Admins can manage MFA for other users.
**Acceptance Criteria**:
- Reset MFA for a user (remove secret and backup codes, force re-enrollment).
- View MFA enrollment status in user management table.
- Modify global MFA policy (which roles are enforced).
- All admin actions logged to audit trail.

### FR-037-007: Audit & Logging
**Description**: All MFA-related events are auditable.
**Acceptance Criteria**:
- Log events: `MFA_ENABLED`, `MFA_DISABLED`, `MFA_VERIFIED`, `MFA_FAILED`, `BACKUP_CODE_USED`, `MFA_RESET_BY_ADMIN`.
- Include IP address, user agent, and timestamp.
- Integrate with existing `ocp_audit_log` table.

---

## Non-Functional Requirements

- **NFR-001**: TOTP verification must complete in < 200ms.
- **NFR-002**: QR code SVG must be < 5KB.
- **NFR-003**: All secrets encrypted at rest (AES-256-GCM).
- **NFR-004**: No external TOTP service dependencies — pure PHP implementation.
- **NFR-005**: Compatible with Google Authenticator, Authy, Microsoft Authenticator, 1Password, Bitwarden.

---

## Security Considerations

- TOTP secrets encrypted with key derived from `AUTH_SECRET` + per-user salt.
- Backup codes hashed with bcrypt (cost factor 12).
- Rate limiting on verification endpoint to prevent brute force.
- Session fixation protection: regenerate session ID after MFA verification.
- CSRF protection on all MFA management endpoints.

---

## Rejected Patterns

| Rejected | Canonical |
|---|---|
| SMS-based 2FA | TOTP only (no SMS gateway dependency) |
| Email-based 2FA | TOTP only (no email server dependency for 2FA) |
| Hardware key (WebAuthn/FIDO2) | Deferred to future feature (higher complexity) |
| Plaintext secret storage | AES-256-GCM encryption |
| Plaintext backup codes | bcrypt hashing |

---

## Acceptance Criteria Summary

| ID | Criterion | Priority |
|---|---|---|
| AC-001 | User can scan QR code and enroll MFA | Required |
| AC-002 | Login requires TOTP after password validation | Required |
| AC-003 | Backup codes work for recovery | Required |
| AC-004 | Admin can enforce MFA per role | Required |
| AC-005 | Rate limiting prevents brute force | Required |
| AC-006 | All events logged to audit trail | Required |
| AC-007 | Self-service disable/regenerate works | Required |
| AC-008 | Integration tests cover all flows | Required |
| AC-009 | Compatible with Google Authenticator and Authy | Required |
