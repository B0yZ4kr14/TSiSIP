# Implementation Plan: Multi-Factor Authentication (MFA) for OCP

## Feature

**Feature Number**: 037  
**Feature Name**: Multi-Factor Authentication (MFA) for OCP  
**Branch**: `037-ocp-mfa-2fa`

## Architecture

### Components

1. **TOTP Engine** (PHP library `OTPHP` or custom implementation)
   - Secret generation (Base32, 32 bytes)
   - TOTP code verification (RFC 6238, ±1 window)
   - QR code generation (SVG via `chillerlan/php-qrcode` or inline SVG)

2. **Database Layer**
   - `ocp_user_mfa` table: user_id, encrypted_secret, enabled_at, last_verified_at, failed_attempts, locked_until
   - `ocp_user_backup_codes` table: user_id, code_hash, used_at
   - `mfa_policy` table: role, enforced, grace_period_days

3. **Login Flow Modification**
   - After password validation, check if MFA is enabled
   - If enabled, render `mfa-verify.php` instead of redirecting to dashboard
   - After TOTP verification, regenerate session ID and redirect to dashboard

4. **Enrollment Flow**
   - `profile.php` → "Security" tab → "Enable 2FA"
   - Generate secret, display QR code SVG
   - Verify first code, show backup codes
   - Store encrypted secret and hashed backup codes

5. **Admin Management**
   - `users.php` → column showing MFA status (enrolled/pending/disabled)
   - Admin can reset MFA for any user
   - `system-config.php` → MFA policy settings

6. **Audit Integration**
   - All MFA events logged via existing `logAuditEvent()`

### Data Model

```sql
-- MFA enrollment per user
CREATE TABLE IF NOT EXISTS ocp_user_mfa (
    user_id UUID PRIMARY KEY REFERENCES ocp_users(id) ON DELETE CASCADE,
    secret_encrypted TEXT NOT NULL,           -- AES-256-GCM encrypted TOTP secret
    enabled_at TIMESTAMP WITH TIME ZONE,
    last_verified_at TIMESTAMP WITH TIME ZONE,
    failed_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP WITH TIME ZONE,
    last_code_window INTEGER                  -- prevent replay
);

-- Backup/recovery codes
CREATE TABLE IF NOT EXISTS ocp_user_backup_codes (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    code_hash VARCHAR(255) NOT NULL,          -- bcrypt hash
    used_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
CREATE INDEX idx_backup_codes_user ON ocp_user_backup_codes(user_id, used_at);

-- MFA policy per role
CREATE TABLE IF NOT EXISTS mfa_policy (
    role VARCHAR(32) PRIMARY KEY,
    enforced BOOLEAN DEFAULT false,
    grace_period_days INTEGER DEFAULT 7,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### Implementation Phases

### Phase 1: Database Schema & TOTP Core
- Create migrations for `ocp_user_mfa`, `ocp_user_backup_codes`, `mfa_policy`
- Implement `lib/totp.php` with secret generation, verification, QR code SVG
- Implement `lib/crypto.php` with AES-256-GCM encrypt/decrypt
- Unit tests for TOTP engine

### Phase 2: Login Flow Integration
- Modify `login.php` to check MFA after password validation
- Create `mfa-verify.php` for TOTP input screen
- Create `mfa-backup.php` for backup code recovery
- Session management: regenerate ID after MFA
- Rate limiting on verification

### Phase 3: Enrollment & Self-Service
- Add MFA section to `profile.php`
- Create `api/v1/mfa-enroll.php` (generate QR, verify first code)
- Create `api/v1/mfa-disable.php` (require password + TOTP)
- Create `api/v1/mfa-regenerate-backup.php`
- Display backup codes during enrollment

### Phase 4: Admin & Policy
- Add MFA status column to `users.php`
- Create `api/v1/mfa-reset.php` (admin only)
- Create `api/v1/mfa-policy.php` (admin only)
- Populate `mfa_policy` defaults

### Phase 5: Audit & Testing
- Ensure all events call `logAuditEvent()`
- Integration tests for enrollment, login, backup codes, admin reset
- Security tests: rate limiting, replay protection, encryption

### Phase 6: Deploy & Documentation
- Update operator runbook with MFA procedures
- Deploy to VPS
- Final validation

## Validation Gates

- [ ] TOTP codes verify correctly with Google Authenticator
- [ ] Backup codes work and are consumed on use
- [ ] Rate limiting blocks brute force after 5 attempts
- [ ] Admin can reset MFA for any user
- [ ] Policy enforcement redirects unenforced users to enrollment
- [ ] All events appear in audit log
- [ ] No plaintext secrets in database
