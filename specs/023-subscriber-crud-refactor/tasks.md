# Feature 023 Tasks

## Phase 0: Security Foundation
- [x] T0.1: Create `docs/security/023-subscriber-crud-refactor-security-assessment.md` — data classification, proxy auth model, input validation, rate limiting design
- [x] T0.2: Create `docs/security/023-subscriber-crud-refactor-threat-model.md` — STRIDE analysis for subscriber proxy, hash tampering, unauthorized modification
- [x] T0.3: Update `docs/security/008-security-evidence-index.md` with Feature 023 entries and expiration dates
- [x] T0.4: MSL applicability review — assess if subscriber HA1 data falls under MSL; document justification with risk acceptance
- [x] T0.5: Write architecture decision record (ADR) — choose MI command vs REST API for subscriber proxy; document trade-offs and constitution alignment
- [x] T0.6: Secure-development verification — scan new/modified PHP files for SQL injection patterns (raw concatenation), XSS vulnerabilities (unescaped output), missing auth checks, and secret leakage

## Phase 1: Proxy Layer Implementation
- [x] T001: Implement subscriber proxy — `subscriber_create`, `subscriber_update`, `subscriber_delete` endpoints/commands
- [x] T002: Implement proxy authentication — validate service secret on every request; reject unauthenticated calls
- [x] T003: Implement rate limiting — max 10 creations/min per source; configurable threshold
- [x] T004: Implement audit logging on proxy layer — `auth_audit_log` entries for all CREATE/UPDATE/DELETE with caller identity
- [x] T005: Validate proxy rejects plaintext passwords — negative test sending plaintext password; verify rejection
- [x] T1.6: Validate proxy accepts precomputed HA1 hashes — positive test with valid hashes; verify database write

## Phase 2: OCP Migration
- [x] T006: Remove direct `subscriber` writes from `web/subscribers.php` — delete INSERT/UPDATE/DELETE statements; preserve SELECT queries
- [x] T007: Create `web/common/subscriber-proxy.php` helper — encapsulate proxy calls, error handling, timeout configuration
- [x] T008: Integrate proxy client into subscriber creation flow — generate HA1 in OCP, call proxy, handle response
- [x] T009: Integrate proxy client into subscriber update flow — generate HA1 (if password changed), call proxy, handle response
- [x] T010: Integrate proxy client into subscriber deletion flow — call proxy with subscriber ID, handle response
- [x] T2.6: Preserve role-based access — `requireRole('devops')` enforced at OCP entry for all subscriber access. Future sprints may introduce `requireRole('admin')` for mutations.
- [x] T2.7: Implement graceful fallback — display user-friendly error when proxy is unreachable; no stack trace leakage
- [x] T2.8: Regression test — subscriber list, search, pagination unchanged; create/update/delete work through proxy

## Phase 3: Validation & Closure
- [x] T011: Run `speckit.spec-validate.validate` on Feature 023 spec — verify all 10 ACs covered
- [x] T012: Run architecture-guard verification — confirm ARCH-PRE-001 resolved; zero constitution violations
- [x] T013: Run brownfield scan — no drift, no rejected patterns introduced
- [x] T014: Update `.specify/memory/architecture_constitution.md` — mark ARCH-PRE-001 as resolved
- [x] T015: Write conventional commit with all Feature 023 changes and push to `main`
- [x] T3.6: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with subscriber management operational procedures

## Security Review Checkpoints

| Checkpoint | Trigger | Gate Condition |
|---|---|---|
| SR-1 | After T0.3 | Threat model must cover proxy injection, hash tampering, unauthorized subscriber modification |
| SR-4 | After T0.6 | All new/modified PHP files must pass secret-leakage scan and secure-development validation with zero findings |
| SR-2 | After T003 | Proxy must reject plaintext passwords, validate all inputs, enforce rate limiting |
| SR-3 | After T008 | OCP must contain zero direct writes to `subscriber`; all mutations route through proxy |

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
| AC2 (proxy accepts HA1) | T001, T1.6 |
| AC3 (zero subscriber writes in OCP) | T006 |
| AC4 (mutations via proxy) | T007, T008, T009, T010 |
| AC5 (HA1 in OCP) | T008, T009 |
| AC6 (audit logging on proxy) | T004 |
| AC7 (rate limiting) | T003 |
| AC8 (TLS/internal network) | T001, T007 |
| AC9 (security assessment) | T0.1 |
| AC10 (threat model) | T0.2 |
| R1 (proxy auth) | T002 |
| R2 (input validation) | T001 |
| R3 (HA1 only) | T005, T1.6 |
| R4 (proxy audit) | T004 |
| R5 (rate limiting) | T003 |
| R7 (TLS/encryption) | T001, T007 |
| R8 (graceful fallback) | T2.7 |
| R9 (RBAC preserved) | T2.6 |
| R10 (regression test) | T2.8 |

## Additional Security Tasks

- [x] T2.9: Run secret-leakage scan on all new PHP files (`web/common/subscriber-proxy.php`, proxy implementation) — verify zero plaintext secrets, credentials, or IP addresses
- [x] T0060: Run CSRF validation test on subscriber mutating forms (create/update/delete) — verify token validation still enforced after proxy integration
