# STRIDE Threat Model — TSiSIP vps-lite Stack

**Date**: 2026-05-23
**Scope**: OpenSIPS, PostgreSQL, RTPengine, Asterisk, OCP, Backup

---

## Trust Boundaries

1. Internet → sip_edge (OpenSIPS, RTPengine)
2. sip_edge → sip_internal (Asterisk)
3. sip_internal → db_internal (PostgreSQL)
4. Control Plane (OCP) → db_internal (read-only)

## Threat Analysis

| Threat | Category | Component | Risk | Mitigation | Status |
|---|---|---|---|---|---|
| SIP spoofing | Spoofing | OpenSIPS | HIGH | Digest auth + IP whitelist | PASS |
| Tampered CDR | Tampering | PostgreSQL | HIGH | Immutable CDR, tenant_id FK | PASS |
| Repudiation of calls | Repudiation | OpenSIPS | MEDIUM | auth_audit_log, CDR | PASS |
| Information disclosure | Information | RTPengine | HIGH | SRTP, topology_hiding("C") | PASS |
| DoS / DDoS | Denial | OpenSIPS | HIGH | pike module, rate limiting | PASS |
| Elevation of privilege | Elevation | OCP | MEDIUM | Role hierarchy, CSRF | PASS |
| Container escape | Elevation | Docker | MEDIUM | cap_drop, no-new-privileges | PASS |
| Secret leakage | Information | Git | HIGH | .gitignore, pre-commit scan | PASS |
| MITM | Tampering | TLS | HIGH | TLS 1.2+, HSTS | PENDING DNS |
| Backup theft | Information | Backup | MEDIUM | AES-256-CBC + PBKDF2 + HMAC-SHA256 encryption | PASS |

## Risk Register

| ID | Threat | Likelihood | Impact | Risk Score | Owner | Review Date |
|---|---|---|---|---|---|---|
| T001 | SIP spoofing | Low | High | Medium | Security | 2026-11-23 |
| T002 | DoS attack | Medium | High | High | Security | 2026-11-23 |
| T003 | MITM | Low | High | Medium | Security | 2026-11-23 |
| T004 | Container escape | Low | Medium | Low | DevOps | 2026-11-23 |
| T005 | Secret leakage | Low | High | Medium | DevOps | 2026-11-23 |
