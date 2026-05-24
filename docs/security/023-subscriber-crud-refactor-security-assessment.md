# Security Assessment — Feature 023: Subscriber CRUD Refactor

**Date**: 2026-05-24
**Feature**: 023 — Subscriber CRUD Refactor (ARCH-PRE-001)
**Assessor**: speckit-implement
**Status**: PENDING → PASS (after implementation)

---

## 1. Data Classification

| Data Element | Classification | Justification |
|---|---|---|
| Subscriber username | PII / Sensitive | Identifies a natural person; linked to SIP credentials |
| Domain | Internal | Tenant-scoped routing metadata |
| HA1 (MD5) | Sensitive / Authentication Material | One-way hash of password; enables offline attacks if leaked |
| HA1_SHA256 | Sensitive / Authentication Material | Stronger hash; still authentication material |
| HA1_SHA512T256 | Sensitive / Authentication Material | Strongest hash; still authentication material |
| Email address | PII | Direct personal identifier under LGPD |
| Tenant ID | Internal | Multi-tenancy scoping |
| Enabled flag | Internal | Operational state |

**MSL Applicability**: YES. Subscriber data contains SIP credentials (HA1 hashes). Although one-way, HA1 enables offline dictionary attacks. All subscriber mutations must be logged, rate-limited, and authenticated.

---

## 2. Proxy Authentication Model

| Aspect | Decision |
|---|---|
| Authentication type | Service-to-service shared secret |
| Secret storage | Docker secret mounted at runtime path |
| Transport | Internal Docker network (sip_internal) — no host-published port |
| Secret rotation | Manual via secret rotation procedure; no hot-reload required |
| Fallback on auth failure | 403 Forbidden; no retry loop |

**Rationale**: Per-user authentication is unnecessary because the proxy is an internal implementation detail. The OCP already authenticates users via session. The proxy layer only needs to verify that the caller is the authorized OCP service.

---

## 3. Input Validation Strategy

| Field | Validation | Location |
|---|---|---|
| Username | Alphanumeric plus dot/underscore/hyphen, max 64 chars | OCP + Proxy |
| Domain | Valid FQDN or IP, max 253 chars | OCP + Proxy |
| HA1 (all variants) | Hex string, exact length (32/64/64 chars) | Proxy (canonical) |
| Email | RFC 5322 subset, max 255 chars | OCP |
| Tenant ID | UUID v4 format | OCP |
| Enabled | Boolean | OCP |

**HA1 Format Enforcement**: The proxy layer rejects any non-hex HA1 value. This prevents injection of malformed data into the database.

**Plaintext Password Rejection**: The proxy explicitly rejects requests containing a password field. Only precomputed HA1 hashes are accepted.

---

## 4. Rate Limiting Design

| Limit | Value | Scope |
|---|---|---|
| Subscriber creations | 10 per minute per source IP | Proxy layer (OpenSIPS htable) |
| Subscriber updates | 30 per minute per source IP | Proxy layer (OpenSIPS htable) |
| Subscriber deletions | 10 per minute per source IP | Proxy layer (OpenSIPS htable) |

**Enforcement**: OpenSIPS htable with 60-second auto-expire. Counter key includes action type and source IP.

**Configurable**: Thresholds read from environment variables at startup.

---

## 5. Threat Summary

| Threat ID | Threat | Mitigation | Residual Risk |
|---|---|---|---|
| T-023-001 | Proxy injection via forged requests | Service secret + internal network | LOW |
| T-023-002 | Hash tampering in transit | Internal network only (no TLS needed) | LOW |
| T-023-003 | Unauthorized subscriber deletion | RBAC in OCP + service secret | LOW |
| T-023-004 | Rate limit bypass | Source IP tracking in proxy | LOW |
| T-023-005 | HA1 leakage via logs | No HA1 in OpenSIPS logs; only username logged | LOW |

---

## 6. Compliance Mapping

| Requirement | Evidence |
|---|---|
| R1 — Proxy authentication | T1.2 implementation + Docker secret |
| R2 — Input validation | T1.1 validation logic + T1.5 negative test |
| R3 — HA1 only, no plaintext | T1.5 rejection test + T2.3/T2.4 integration |
| R4 — Audit logging | T1.4 auth_audit_log entries |
| R5 — Rate limiting | T1.3 htable implementation |
| R6 — Zero direct writes | T2.1 removal of PDO writes |
| R7 — Secure communication | Internal Docker network sip_internal |
| R8 — Graceful fallback | T2.7 user-friendly errors |
| R9 — RBAC preserved | T2.6 requireRole checks |
| R10 — Regression testing | T2.8 end-to-end test |
