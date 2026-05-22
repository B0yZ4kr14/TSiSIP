# Threat Model — Feature 020: OCP Critical Tool Gap Closure

**Document ID**: SEC-020-EVI-002  
**Date**: 2026-05-19  
**Methodology**: STRIDE  
**Scope**: Dialog Viewer, MI Commands Runner, Statistics Monitor, Dialplan Manager, Domains Manager, TLS Management UI  

---

## 1. Assets

| Asset | Value | Location |
|---|---|---|
| Active SIP dialog data | High | OpenSIPS `dialog` module / PostgreSQL |
| MI command interface | Critical | OpenSIPS MI socket (internal network) |
| Dialplan rules | High | PostgreSQL `dialplan` table |
| Domain configuration | Medium | PostgreSQL `domain` table |
| TLS certificates | Critical | `secrets/` directory (container-mounted) |
| Statistics data | Medium | OpenSIPS `statistics` module |

---

## 2. STRIDE Analysis

### 2.1 Dialog Viewer

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Spoofing: attacker impersonates devops user | Spoofing | Medium | Session auth + bcrypt |
| Tampering: modify dialog data | Tampering | Low | Read-only design |
| Repudiation: deny viewing dialogs | Repudiation | Low | Audit log |
| Information Disclosure: expose call metadata | Information Disclosure | Medium | RBAC (devops+) |
| Denial of Service: flood dialog queries | DoS | Low | Pagination + rate limiting |
| Elevation of Privilege: access without auth | Elevation | Low | `requireRole()` gate |

### 2.2 MI Commands Runner

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Spoofing: impersonate admin to run privileged commands | Spoofing | Medium | Session auth + role check |
| Tampering: inject malicious MI command | Tampering | High | Whitelist (hardcoded) |
| Repudiation: deny executing command | Repudiation | Medium | Audit log |
| Information Disclosure: MI output leaks secrets | Information Disclosure | Medium | `htmlspecialchars()` + no secret exposure |
| DoS: flood MI commands | DoS | Medium | Rate limiting + command logging |
| Elevation: non-admin runs admin command | Elevation | High | `requireRole('admin')` for privileged commands |

### 2.3 Statistics Monitor

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Information Disclosure: statistics reveal load patterns | Information Disclosure | Low | RBAC (devops+) |
| DoS: excessive polling | DoS | Low | 30s auto-refresh only |

### 2.4 Dialplan Manager

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Tampering: malicious dialplan rule | Tampering | High | Input validation + CSRF |
| SQL Injection | Tampering | High | PDO prepared statements |
| Repudiation: deny rule change | Repudiation | Medium | Audit log |

### 2.5 Domains Manager

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Tampering: unauthorized domain addition | Tampering | Medium | RBAC + CSRF |
| SQL Injection | Tampering | High | PDO prepared statements |

### 2.6 TLS Management

| Threat | Category | Risk | Mitigation |
|---|---|---|---|
| Tampering: reload with invalid cert | Tampering | Medium | Admin-only + confirmation |
| DoS: frequent reloads | DoS | Low | Audit log + admin gate |
| Elevation: non-admin triggers reload | Elevation | High | `requireRole('admin')` |

---

## 3. Attack Scenarios

### Scenario 1: MI Command Injection
**Attacker**: Compromised devops account  
**Vector**: POST custom MI command to `mi-commands.php`  
**Impact**: Unauthorized runtime configuration change  
**Mitigation**: Whitelist rejects unknown commands (HTTP 403). Admin commands require admin role.

### Scenario 2: Dialog Data Harvesting
**Attacker**: Insider with devops access  
**Vector**: Browse dialog viewer to enumerate active calls  
**Impact**: Exposure of call patterns and peer IPs  
**Mitigation**: Dialog data is operational metadata, not PII. Audit log tracks access.

### Scenario 3: Privilege Escalation via TLS Reload
**Attacker**: Devops user attempting admin action  
**Vector**: Attempt `tls_reload` via MI commands  
**Impact**: Service disruption if cert is invalid  
**Mitigation**: `requireRole('admin')` blocks the attempt.

---

## 4. Risk Summary

| Risk Level | Count | Threats |
|---|---|---|
| Critical | 0 | — |
| High | 3 | MI command injection, SQL injection, privilege escalation |
| Medium | 8 | Information disclosure, spoofing, tampering (non-injection) |
| Low | 5 | DoS, repudiation, read-only tampering |

**All High risks are mitigated by design controls (whitelist, PDO prepared statements, RBAC).**

---

## 5. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Threat Model Author | Security Governance | 2026-05-19 | Complete |
| Independent Reviewer | Architecture Review | 2026-05-19 | Approved |
| Security Owner | @b0yz4kr14 | 2026-05-19 | Approved |
