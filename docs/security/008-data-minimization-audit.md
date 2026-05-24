# Data Minimization Audit — TSiSIP

**Date**: 2026-05-23
**Auditor**: Architecture Guard / Security Review

---

## Subscriber Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| username | Yes | SIP URI identifier | Yes — URI only, no real name |
| domain | Yes | Routing | Yes |
| ha1 | Yes | Digest auth | Yes — hash only, no plaintext password |
| ha1_sha256 | Yes | SHA-256 digest | Yes |
| ha1_sha512t256 | Yes | SHA-512/256 digest | Yes |
| tenant_id | Yes | Multi-tenancy isolation | Yes |
| created_at | Yes | Audit | Yes |

**Finding**: PASS — No excess fields. HA1 precomputation prevents plaintext storage.

## CDR Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| callid | Yes | Call correlation | Yes |
| caller_id | Yes | Billing/ANATEL | Yes — pseudonymized optional |
| callee_id | Yes | Billing/ANATEL | Yes — pseudonymized optional |
| duration | Yes | Billing | Yes |
| tenant_id | Yes | Isolation | Yes |
| start_time | Yes | Record | Yes |

**Finding**: PASS — No excess fields.

## OCP Users Table

| Column | Required | Purpose | Minimized? |
|---|---|---|---|
| username | Yes | Login | Yes |
| password_hash | Yes | bcrypt auth | Yes — hash only |
| role | Yes | Authorization | Yes |
| tenant_id | Yes | Isolation | Yes |
| force_password_change | Yes | Security policy | Yes |

**Finding**: PASS — No excess fields.

## Recommendations

- Consider pseudonymizing caller_id/callee_id by default for all tenants
- Review `auth_audit_log` retention — 1 year may be reduced to 6 months if risk assessment supports
