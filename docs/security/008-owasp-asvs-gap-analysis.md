# OWASP ASVS 4.0 Gap Analysis

**Date**: 2026-05-21
**Scope**: OCP web interface, OpenSIPS SIP edge, Docker runtime
**Assessor**: Architecture Guard + Security Governance preset

## Summary

| Category | Status | Gaps |
|---|---|---|
| V1 Architecture | Mostly Met | SLSA provenance missing |
| V2 Authentication | Met | bcrypt, session.cookie_secure, forced passphrase change |
| V3 Session Management | Partial | CSRF missing on change-password.php |
| V4 Access Control | Met | Role hierarchy enforced |
| V5 Validation | Met | Input validation, PDO prepared statements |
| V6 Cryptography | Met | HA1-only, TLS 1.2+, HSTS preload |
| V7 Error Handling | Partial | ocp_password_changes audit table missing |
| V8 Data Protection | Met | pgcrypto encryption, backup AES-256-GCM |
| V9 Communication | Met | TLS termination, topology hiding |
| V10 Malicious Code | Met | No eval, no raw SQL, cap_drop ALL |
| V11 Business Logic | Met | Rate limiting, pike, circuit breaker |
| V12 File Handling | N/A | No user-uploaded files |
| V13 API | Met | SIP validation, header sanitization |

## Critical Gaps

### V3.5.1 — CSRF Protection for State-Changing Operations
- **Gap**: web/change-password.php does not validate CSRF tokens.
- **Risk**: HIGH — passphrase change is a privileged state-changing operation.
- **Evidence**: grep shows zero csrf references in change-password.php
- **Remediation**: Add require_once common/csrf.php and validateCsrfToken() check.

### V7.1.1 — Audit Logging
- **Gap**: ocp_password_changes table is defined in security_constitution.md section 7 but has no DDL.
- **Risk**: MEDIUM — compliance gap for LGPD/SOC 2 audit trail.
- **Evidence**: grep shows no DDL for ocp_password_changes in db/init/
- **Remediation**: Create table in db/init/02-tsisip-extensions.sql and INSERT in change-password.php.

## Positive Findings

- V5.3.1: 205 input validation calls across PHP codebase.
- V8.2.1: Nginx reverse proxy emits CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.
- V11.1.1: CSRF token uses random_bytes(32) via bin2hex().
- V2.1.2: Passphrase hashes use bcrypt (password_hash()).

## References
- security_constitution.md
- web/common/csrf.php
- web/change-password.php
- deploy/nginx/tsisip-reverse-proxy.conf
