# Threat Model — Feature 023: Subscriber CRUD Refactor

**Date**: 2026-05-24
**Feature**: 023 — Subscriber CRUD Refactor
**Method**: STRIDE
**Status**: PENDING → PASS (after implementation)

---

## 1. Scope

This threat model covers the subscriber proxy layer introduced in Feature 023:
- OCP to OpenSIPS MI command interface
- OpenSIPS to PostgreSQL subscriber mutations
- HA1 hash generation and transport

## 2. Trust Boundaries

```
[Internet] --(HTTPS)--> [OCP] --(MI HTTP + Secret)--> [OpenSIPS] --(SQL)--> [PostgreSQL]
   untrusted         trusted        trusted                trusted         trusted
```

- **OCP**: Authenticates human users; trusted boundary
- **OpenSIPS MI**: Internal service boundary; requires service secret
- **PostgreSQL**: Data boundary; only OpenSIPS writes subscriber data

## 3. STRIDE Analysis

### Spoofing (S)

| Threat | Description | Mitigation |
|---|---|---|
| S-001 | Attacker impersonates OCP to call MI commands | Header validation; secret mounted as Docker secret |
| S-002 | Attacker spoofs source IP to bypass rate limiting | Docker internal network; no direct external access to MI HTTP |

### Tampering (T)

| Threat | Description | Mitigation |
|---|---|---|
| T-001 | Attacker modifies HA1 hash in transit to inject weak hash | Hex validation on proxy; length checks; internal network only |
| T-002 | Attacker modifies subscriber ID to delete another tenant's user | Tenant scoping in SQL WHERE clause; RBAC enforcement |

### Repudiation (R)

| Threat | Description | Mitigation |
|---|---|---|
| R-001 | Attacker denies performing subscriber mutation | auth_audit_log entries for every CREATE/UPDATE/DELETE |

### Information Disclosure (I)

| Threat | Description | Mitigation |
|---|---|---|
| I-001 | HA1 hashes leaked in OpenSIPS logs | Log only username; never log HA1 values |
| I-002 | Proxy error messages leak internal paths | Sanitized error messages in subscriber-proxy.php; no stack traces |

### Denial of Service (D)

| Threat | Description | Mitigation |
|---|---|---|
| D-001 | Attacker floods proxy with subscriber creations | Rate limiting: 10 creations/min per IP; htable counter |
| D-002 | Attacker triggers expensive SQL via malformed input | Prepared statements in sql_query; input validation before SQL |

### Elevation of Privilege (E)

| Threat | Description | Mitigation |
|---|---|---|
| E-001 | Devops user elevates to admin by calling proxy directly | Proxy does not enforce RBAC; RBAC enforced in OCP layer (defense in depth) |
| E-002 | Attacker exploits MI interface to run arbitrary SQL | sql_query uses parameterized queries; no dynamic SQL construction |

## 4. Risk Register

| ID | Threat | Likelihood | Impact | Risk | Status |
|---|---|---|---|---|---|
| S-001 | OCP impersonation | Low | High | Medium | MITIGATED (service secret) |
| T-001 | HA1 tampering | Low | Medium | Low | MITIGATED (hex validation) |
| R-001 | Repudiation | Low | Medium | Low | MITIGATED (audit logging) |
| I-001 | Hash leakage | Low | High | Medium | MITIGATED (no hash logging) |
| D-001 | Creation flood | Medium | Medium | Medium | MITIGATED (rate limiting) |
| E-001 | Privilege escalation | Low | High | Medium | ACCEPTED (OCP enforces RBAC) |

## 5. Assumptions

- Docker network sip_internal is not reachable from the host or internet.
- Docker secret is generated with sufficient entropy (>= 256 bits).
- OCP session management is secure (addressed in Feature 020).
- OpenSIPS sql_query module uses parameterized queries (module guarantee).
