## Summary

This plan implements the feature for the TSiSIP SIP edge-proxy platform.

## Technical Context

- **OpenSIPS 3.6 LTS**: Core SIP proxy and signaling edge
- **PostgreSQL**: Database backend for configuration and state
- **Docker & Docker Compose**: Container orchestration and deployment

## Project Structure

Relevant directories and files for this feature are located under specs/023-subscriber-crud-refactor/.

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
  - `requireRole('devops')` enforced at OCP entry for all subscriber page access
  - Future sprints may introduce `requireRole('admin')` for mutations; proxy layer validates service secret only
- [ ] W2.5: Regression test — verify existing subscriber functionality
  - List, search, pagination unchanged
  - Create, update, delete work through proxy
  - Audit log entries present for all operations

## Wave 2.5: Security Validation

**Agents:** `security`, `reviewer`

**Security Review Checkpoint SR-4** (after W2.5): All new/modified PHP files must pass secret-leakage scan and secure-development validation with zero findings.

- [ ] W2.5a: Run secret-leakage scan on `web/common/subscriber-proxy.php` and proxy implementation files
- [ ] W2.5b: Run CSRF validation test on subscriber mutating forms after proxy integration
- [ ] W2.5c: Run SQL injection scan — verify no raw concatenation in new proxy client code
- [ ] W2.5d: Run XSS scan — verify all proxy output wrapped in `htmlspecialchars()`

## Wave 3: Validation & Closure

**Agents:** `docs`, `release`

- [ ] W3.1: Run `speckit.spec-validate.validate` on Feature 023 spec
- [ ] W3.2: Run architecture-guard verification — confirm ARCH-PRE-001 resolved
- [ ] W3.3: Run brownfield scan — zero new drift, zero rejected patterns
- [ ] W3.4: Update `.specify/memory/architecture_constitution.md` — mark ARCH-PRE-001 as resolved
- [ ] W3.5: Write conventional commit and push to `main`
- [ ] W3.6: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with subscriber management procedures

## Tech Stack

| Layer | Technology | Role |
|---|---|---|
| Proxy (REJECTED Option A) | OpenSIPS 3.6 LTS + `sql_query` module | MI command handlers — rejected: no parameterized SQL via MI |
| Proxy (SELECTED Option B) | PHP 8.2 + Apache + PDO | Thin REST API microservice (`tsisip/admin-api`) |
| OCP Client | PHP 8.2 + cURL | Proxy client helper in OCP frontend |
| Database | PostgreSQL 16 | `subscriber` table (schema unchanged) |
| Auth | Docker secrets | Service-to-service shared secret |

## File Structure (Option B — REST)

```
docker/admin-api/
├── Dockerfile
└── src/
    ├── index.php          # Router
    ├── config.php         # DB connection, secret validation
    ├── subscriber-api.php # CREATE/UPDATE/DELETE endpoints
    └── audit-logger.php   # Proxy-layer audit logging

web/common/
└── subscriber-proxy.php   # OCP proxy client helper

web/
└── subscribers.php        # Refactored: zero direct writes
```

## Docker Network Model (Option B)

If REST approach chosen:
- New service `admin-api` joins `sip_internal` and `db_internal`
- OCP calls `admin-api` over `sip_internal` (no host-published port)
- `admin-api` writes to PostgreSQL over `db_internal`
- Service secret mounted as Docker secret at `/run/secrets/admin_api_secret`

## Test Strategy

| Test | Scope | Tool |
|---|---|---|
| Unit | Proxy input validation, HA1 format checks | PHPUnit (if Option B) |
| Integration | End-to-end subscriber CRUD via proxy | `scripts/test-subscriber-proxy.sh` |
| Security | Secret leakage, CSRF, SQL injection, XSS | grep-based scans + manual review |
| Architecture | Layer boundary compliance | `grep` for `INSERT/UPDATE/DELETE` on `subscriber` |
| Regression | Existing subscriber list/read unchanged | Browser-driven or curl-based |

## Rollback Plan

If proxy implementation fails in production:
1. Revert `web/subscribers.php` to direct PDO writes (last known working version from git)
2. Disable proxy service (if Option B)
3. Mark ARCH-PRE-001 as re-opened in `architecture_constitution.md`
4. Emergency brownfield scan to confirm no regressions

## Dependency Graph

```
W0 (Security + ADR) → W1 (Proxy) → W2 (OCP Migration) → W2.5 (Security Validation) → W3 (Validation)
       ↓                    ↓              ↓                        ↓
     SR-1                 SR-2           SR-3                     SR-4
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

## Evidence & Compliance

| Evidence | Path | Produced By |
|---|---|---|
| Security assessment | `docs/security/023-subscriber-crud-refactor-security-assessment.md` | W0.1 |
| Threat model | `docs/security/023-subscriber-crud-refactor-threat-model.md` | W0.2 |
| Security evidence index update | `docs/security/008-security-evidence-index.md` | W0.3 |
| MSL applicability justification | `docs/security/023-subscriber-crud-refactor-msl.md` | W0.4 |
| ADR | `docs/architecture/023-adr-subscriber-proxy.md` | W0.5 |
| Secret-leakage scan report | `reports/secret-leakage-023.md` | W2.5a |
| Architecture compliance evidence | `evidence/remediation/ciclo-023/` | W3.2, W3.3 |
