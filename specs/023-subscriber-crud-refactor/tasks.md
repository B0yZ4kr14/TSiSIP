# Feature 023 Tasks

## Wave 0: Security Foundation
- [ ] T0.1: Create `docs/security/023-subscriber-crud-refactor-security-assessment.md` — data classification, proxy auth model, input validation, rate limiting design
- [ ] T0.2: Create `docs/security/023-subscriber-crud-refactor-threat-model.md` — STRIDE analysis for subscriber proxy, hash tampering, unauthorized modification
- [ ] T0.3: Update `docs/security/008-security-evidence-index.md` with Feature 023 entries and expiration dates
- [ ] T0.4: MSL applicability review — assess if subscriber HA1 data falls under MSL; document justification with risk acceptance
- [ ] T0.5: Write architecture decision record (ADR) — choose MI command vs REST API for subscriber proxy; document trade-offs and constitution alignment
- [ ] T0.6: Secure-development verification — scan new/modified PHP files for SQL injection patterns (raw concatenation), XSS vulnerabilities (unescaped output), missing auth checks, and secret leakage

## Wave 1: Proxy Layer Implementation
- [ ] T1.1: Implement subscriber proxy — `subscriber_create`, `subscriber_update`, `subscriber_delete` endpoints/commands
- [ ] T1.2: Implement proxy authentication — validate service secret on every request; reject unauthenticated calls
- [ ] T1.3: Implement rate limiting — max 10 creations/min per source; configurable threshold
- [ ] T1.4: Implement audit logging on proxy layer — `auth_audit_log` entries for all CREATE/UPDATE/DELETE with caller identity
- [ ] T1.5: Validate proxy rejects plaintext passwords — negative test sending plaintext password; verify rejection
- [ ] T1.6: Validate proxy accepts precomputed HA1 hashes — positive test with valid hashes; verify database write

## Wave 2: OCP Migration
- [ ] T2.1: Remove direct `subscriber` writes from `web/subscribers.php` — delete INSERT/UPDATE/DELETE statements; preserve SELECT queries
- [ ] T2.2: Create `web/common/subscriber-proxy.php` helper — encapsulate proxy calls, error handling, timeout configuration
- [ ] T2.3: Integrate proxy client into subscriber creation flow — generate HA1 in OCP, call proxy, handle response
- [ ] T2.4: Integrate proxy client into subscriber update flow — generate HA1 (if password changed), call proxy, handle response
- [ ] T2.5: Integrate proxy client into subscriber deletion flow — call proxy with subscriber ID, handle response
- [ ] T2.6: Preserve role-based access — `requireRole('devops')` for read; `requireRole('admin')` for mutations
- [ ] T2.7: Implement graceful fallback — display user-friendly error when proxy is unreachable; no stack trace leakage
- [ ] T2.8: Regression test — subscriber list, search, pagination unchanged; create/update/delete work through proxy

## Wave 3: Validation & Closure
- [ ] T3.1: Run `speckit.spec-validate.validate` on Feature 023 spec — verify all 10 ACs covered
- [ ] T3.2: Run architecture-guard verification — confirm ARCH-PRE-001 resolved; zero constitution violations
- [ ] T3.3: Run brownfield scan — no drift, no rejected patterns introduced
- [ ] T3.4: Update `.specify/memory/architecture_constitution.md` — mark ARCH-PRE-001 as resolved
- [ ] T3.5: Write conventional commit with all Feature 023 changes and push to `main`
- [ ] T3.6: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with subscriber management operational procedures

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T0.3 | Threat model must cover proxy injection, hash tampering, unauthorized subscriber modification |
| SR-4 | After T0.6 | All new/modified PHP files must pass secret-leakage scan and secure-development validation with zero findings |
| SR-2 | After T1.3 | Proxy must reject plaintext passwords, validate all inputs, enforce rate limiting |
| SR-3 | After T2.3 | OCP must contain zero direct writes to `subscriber`; all mutations route through proxy |

## Dependency Graph

```
W0 (Security + ADR) → W1 (Proxy) → W2 (OCP Migration) → W3 (Validation)
       ↓                    ↓              ↓
     SR-1                 SR-2           SR-3
```

## Traceability Matrix

| AC | Task(s) |
|---|---|
| AC1 (ADR approves approach) | T0.5 |
| AC2 (proxy accepts HA1) | T1.1, T1.6 |
| AC3 (zero subscriber writes in OCP) | T2.1 |
| AC4 (mutations via proxy) | T2.2, T2.3, T2.4, T2.5 |
| AC5 (HA1 in OCP) | T2.3, T2.4 |
| AC6 (audit logging on proxy) | T1.4 |
| AC7 (rate limiting) | T1.3 |
| AC8 (TLS/internal network) | T1.1, T2.2 |
| AC9 (security assessment) | T0.1 |
| AC10 (threat model) | T0.2 |
| R1 (proxy auth) | T1.2 |
| R2 (input validation) | T1.1 |
| R3 (HA1 only) | T1.5, T1.6 |
| R4 (proxy audit) | T1.4 |
| R5 (rate limiting) | T1.3 |
| R6 (read-only validation) | T2.1, T3.2 |
| R7 (TLS/encryption) | T1.1, T2.2 |
| R8 (graceful fallback) | T2.7 |
| R9 (RBAC preserved) | T2.6 |
| R10 (regression test) | T2.8 |

## Additional Security Tasks

- [ ] T2.9: Run secret-leakage scan on all new PHP files (`web/common/subscriber-proxy.php`, proxy implementation) — verify zero plaintext secrets, credentials, or IP addresses
- [ ] T2.10: Run CSRF validation test on subscriber mutating forms (create/update/delete) — verify token validation still enforced after proxy integration
