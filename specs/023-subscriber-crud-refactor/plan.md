# Feature 023 Implementation Plan

## Wave 0: Security Foundation

**Agents:** `security`, `doc-forensics`

**Security Review Checkpoint SR-1** (after W0.3): Threat model must cover proxy injection, hash tampering, and unauthorized subscriber modification.

- [ ] W0.1: Create `docs/security/023-subscriber-crud-refactor-security-assessment.md`
  - Data classification for subscriber data (PII — SIP credentials)
  - Proxy authentication model (service secret vs per-user)
  - Input validation strategy for HA1 hash fields
  - Rate limiting and abuse prevention design
- [ ] W0.2: Create `docs/security/023-subscriber-crud-refactor-threat-model.md`
  - STRIDE analysis for subscriber proxy
  - Threat: Proxy injection via forged HA1 hashes
  - Threat: Unauthorized subscriber deletion
  - Threat: Hash tampering in transit
- [ ] W0.3: Update `docs/security/008-security-evidence-index.md` with Feature 023 entries
- [ ] W0.4: MSL Applicability Review
  - Subscriber data contains SIP credentials — assess if PII under LGPD
  - HA1 hashes are one-way but still sensitive
- [ ] W0.5: Architecture decision record (ADR) — choose MI command vs REST API approach
  - Document trade-offs, constitution alignment, and operational complexity

## Wave 1: Proxy Layer Implementation

**Agent:** `coder`

**Security Review Checkpoint SR-2** (after W1.3): Proxy must reject plaintext passwords, validate all inputs, and enforce rate limiting.

- [ ] W1.1: Implement subscriber proxy layer (Option A or B from ADR)
  - If MI: Add `subscriber_create`, `subscriber_update`, `subscriber_delete` to OpenSIPS config
  - If REST: Create `docker/admin-api/` with Dockerfile, endpoints, and PDO prepared statements
  - Accept precomputed HA1 hashes only; reject plaintext passwords
  - Validate username, domain, hash format before DB write
- [ ] W1.2: Implement proxy authentication
  - Service secret validation (Docker secret or env var)
  - Reject requests without valid credential
- [ ] W1.3: Implement rate limiting on proxy
  - Max 10 subscriber creations per minute per source IP
  - Configurable threshold via environment variable
- [ ] W1.4: Implement audit logging on proxy layer
  - Log all CREATE/UPDATE/DELETE to `auth_audit_log`
  - Include caller identity, timestamp, result, and reason on failure

## Wave 2: OCP Migration

**Agent:** `coder`

**Security Review Checkpoint SR-3** (after W2.3): OCP must contain zero direct writes to `subscriber`; all mutations route through proxy.

- [ ] W2.1: Refactor `web/subscribers.php` — remove direct PDO writes
  - Remove `INSERT INTO subscriber` statement
  - Remove `UPDATE subscriber` statements
  - Remove `DELETE FROM subscriber` statement
  - Preserve `SELECT` queries for list/read operations
- [ ] W2.2: Implement proxy client in OCP
  - Create `web/common/subscriber-proxy.php` helper
  - Call proxy with precomputed HA1 hashes
  - Handle proxy unavailability with user-friendly error (no stack traces)
- [ ] W2.3: Preserve HA1 generation in OCP
  - Keep `generateHa1Hashes()` usage in subscriber creation/update flows
  - Pass hashes to proxy client instead of writing to DB directly
- [ ] W2.4: Preserve role-based access
  - `requireRole('devops')` for list/read
  - `requireRole('admin')` for CREATE/UPDATE/DELETE (or proxy enforces)
- [ ] W2.5: Regression test — verify existing subscriber functionality
  - List, search, pagination unchanged
  - Create, update, delete work through proxy
  - Audit log entries present for all operations

## Wave 3: Validation & Closure

**Agents:** `docs`, `release`

- [ ] W3.1: Run `speckit.spec-validate.validate` on Feature 023 spec
- [ ] W3.2: Run architecture-guard verification — confirm ARCH-PRE-001 resolved
- [ ] W3.3: Run brownfield scan — zero new drift, zero rejected patterns
- [ ] W3.4: Update `.specify/memory/architecture_constitution.md` — mark ARCH-PRE-001 as resolved
- [ ] W3.5: Write conventional commit and push to `main`
- [ ] W3.6: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with subscriber management procedures

## Dependency Graph

```
W0 (Security + ADR) → W1 (Proxy) → W2 (OCP Migration) → W3 (Validation)
       ↓                    ↓              ↓
     SR-1                 SR-2           SR-3
```

## MSL Applicability

| Aspect | Assessment |
|---|---|
| Subscriber data contains SIP credentials | **MSL-relevant** — HA1 hashes are sensitive authentication material |
| Mitigation: proxy layer + audit logging + rate limiting | Justification for controlled access |
| **Action**: Document MSL controls in W0.4 | Required |

## Supply-Chain Notes

- If Option A (MI): No new Docker images.
- If Option B (REST): New Docker image `tsisip/admin-api` required.
- No new PHP extensions beyond existing PDO.
- No new npm packages.
