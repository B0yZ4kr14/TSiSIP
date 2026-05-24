# MSL Applicability Review — Feature 023: Subscriber CRUD Refactor

**Date**: 2026-05-24
**Feature**: 023
**Assessor**: speckit-implement

---

## 1. MSL Relevance Assessment

| Criterion | Assessment |
|---|---|
| Contains personal data (PII) | **YES** — subscriber username and email address identify natural persons |
| Contains sensitive data | **YES** — HA1 hashes are authentication material |
| Subject to LGPD | **YES** — SIP credentials are sensitive personal data under Art. 5, II |
| Data volume | LOW — administrative mutations, not bulk user registration |
| Cross-border transfer | NO — all processing within VPS jurisdiction |

## 2. Risk Acceptance

The subscriber proxy refactor **reduces** MSL risk by:
- Removing direct database writes from the web tier (OCP)
- Centralizing mutation authority in the OpenSIPS layer
- Adding audit logging for every mutation
- Enforcing rate limiting to prevent abuse

## 3. Controls

| Control | Implementation |
|---|---|
| Access control | Service secret + internal network + OCP RBAC |
| Audit trail | auth_audit_log entries for all CREATE/UPDATE/DELETE |
| Data minimization | Only HA1 hashes stored; plaintext passwords never persisted |
| Integrity | Hex validation on all HA1 fields |
| Availability | Rate limiting prevents resource exhaustion |

## 4. Justification

Subscriber HA1 data falls under MSL/LGPD sensitive data classification. The proxy layer architecture implemented in Feature 023 strengthens compliance by enforcing layered access controls and comprehensive audit logging. The residual risk is **LOW** and **ACCEPTED**.
