# Feature 023: Subscriber CRUD Refactor

## Overview

Feature 020's post-implementation architecture review discovered **ARCH-PRE-001**: `web/subscribers.php` writes directly to the `subscriber` table, violating the Control Plane → Database layer boundary defined in `.specify/memory/architecture_constitution.md`. The OCP (Control Plane) must never write to the `subscriber` table directly; all subscriber mutations must route through the OpenSIPS layer or a dedicated proxy service.

This feature refactors subscriber management so that HA1 generation remains in the OCP (trusted control plane), but all INSERT/UPDATE/DELETE operations on the `subscriber` table are delegated to the OpenSIPS layer.

## Security Governance Preset

### Memory-Safe Language Assessment

| Language | Memory-Safe | Justification |
|---|---|---|
| PHP 8.2 | No | Managed runtime with GC; buffer overflows possible in extensions. Mitigated via Docker image pinning and Trivy scanning. |
| SQL (PostgreSQL) | N/A | Declarative; managed by PostgreSQL 16. |
| C (OpenSIPS modules) | No | OpenSIPS 3.6 LTS is written in C. Memory safety relies on code review, static analysis, and long-term stability of the LTS branch. |

### Framework Relevance

| Framework | Relevance | Status |
|---|---|---|
| NIST SSDF | Relevant | Input validation, access control, secure API design |
| CWE Top 25 | Relevant | CWE-89 (SQL injection), CWE-306 (missing auth), CWE-200 (information exposure) |
| OWASP ASVS | Relevant | V4 Level 2 — API endpoints require auth, audit logging, input validation |
| SBOM | Relevant | No new base images; may add new Docker service if REST approach chosen |
| VEX | Relevant | Vulnerability disclosure for any CVEs in new OpenSIPS module or microservice |
| SLSA | Relevant | Provenance tracking for Docker image builds |

### Security Evidence Artefacts

- Create: `docs/security/023-subscriber-crud-refactor-security-assessment.md`
- Create: `docs/security/023-subscriber-crud-refactor-threat-model.md`
- Update: `docs/security/008-security-evidence-index.md`

## Motivation

The current `web/subscribers.php` performs direct PDO INSERT/UPDATE/DELETE on the `subscriber` table. This violates the architecture constitution's layer boundary rules:

- **OpenSIPS layer** owns SIP signaling, auth, and routing — including subscriber credential management.
- **OCP layer** owns administrative UI and read-only status — it may read subscriber data but must not write it directly.

Without this refactor:
- OCP bypasses OpenSIPS auth logic when creating subscribers
- HA1 hashes are generated in OCP but could be written inconsistently if OpenSIPS has different hash expectations
- Audit logging for subscriber mutations is fragmented (some in OCP, some missing)
- The architecture constitution violation (ARCH-PRE-001) remains unresolved

## Functional Goals

1. **Design Subscriber Proxy API** — Define the contract for subscriber CREATE, UPDATE, DELETE via OpenSIPS MI commands or REST API
2. **Implement Proxy Layer** — Build the OpenSIPS-side mechanism that accepts precomputed HA1 hashes and writes to PostgreSQL
3. **Migrate OCP Subscriber Page** — Remove all direct `subscriber` table writes from `web/subscribers.php`; replace with proxy calls
4. **Preserve HA1 Generation** — Keep `generateHa1Hashes()` in OCP; pass precomputed hashes to the proxy
5. **Centralize Audit Logging** — Ensure all subscriber mutations are logged to `auth_audit_log` regardless of caller
6. **Validate Layer Compliance** — Confirm OCP contains zero direct write SQL against `subscriber`

## Non-Goals

- Changing the HA1 generation algorithm or hash columns (`ha1`, `ha1_sha256`, `ha1_sha512t256`)
- Modifying the `subscriber` table schema
- Adding new authentication methods (remains SIP Digest)
- Real-time subscriber synchronization (eventual consistency acceptable)
- Multi-tenant subscriber isolation (out of scope; tracked separately)

## Security Requirements

| ID | Requirement |
|---|---|
| R1 | Proxy API requires authentication — OCP must present a valid service credential |
| R2 | All proxy inputs are validated — username, domain, and hash fields sanitized before database write |
| R3 | Precomputed HA1 hashes only — proxy must reject any request containing plaintext passwords |
| R4 | Audit logging on proxy layer — all CREATE/UPDATE/DELETE logged to `auth_audit_log` with caller identity |
| R5 | Rate limiting on proxy — prevent bulk subscriber creation abuse |
| R6 | OCP read-only validation — `web/subscribers.php` must contain zero `INSERT/UPDATE/DELETE` on `subscriber` |
| R7 | TLS for proxy communication — OCP-to-proxy traffic encrypted via mTLS or internal network TLS |
| R8 | Graceful fallback — if proxy is unavailable, OCP shows user-friendly error without leaking internal paths |
| R9 | Role-based access preserved — `requireRole('devops')` enforced at OCP entry. Subscriber mutations are implicitly restricted to authenticated devops users. Future sprints may introduce `requireRole('admin')` for mutations; the proxy layer does not enforce RBAC (defense in depth: OCP enforces, proxy validates service secret only). |
| R10 | Regression test — existing subscriber list, search, and pagination functionality unchanged |

## Acceptance Criteria

- [ ] AC1: Architecture decision record (ADR) approves either MI command or REST API approach for subscriber proxy
- [ ] AC2: Proxy implementation accepts precomputed HA1 hashes and writes to `subscriber` table via validated PDO prepared statements
- [ ] AC3: `web/subscribers.php` contains zero `INSERT INTO subscriber`, `UPDATE subscriber`, or `DELETE FROM subscriber` statements
- [ ] AC4: All subscriber mutations route through the proxy layer; OCP never writes to `subscriber` directly
- [ ] AC5: HA1 generation (`generateHa1Hashes()`) remains in OCP; hashes passed to proxy in request payload
- [ ] AC6: Audit logging covers success and failure paths for all subscriber operations on the proxy layer
- [x] AC7: Rate limiting enforces hard thresholds: max 10 subscriber creations, 30 updates, and 10 deletions per minute per source IP. Thresholds are configurable via environment variables (`SUBSCRIBER_CREATE_RATE_LIMIT`, `SUBSCRIBER_UPDATE_RATE_LIMIT`, `SUBSCRIBER_DELETE_RATE_LIMIT`).
- [x] AC8: OCP-to-proxy communication is confined to the internal Docker network (`sip_internal`). The network is not routable from the host or internet; TLS is not required because traffic never leaves the container network boundary.
- [ ] AC9: Security assessment document exists and is approved
- [ ] AC10: Threat model covers proxy injection, unauthorized subscriber modification, and hash tampering risks

## Architecture Decisions

### AD-1: OpenSIPS MI Command vs REST API
Two approaches for the proxy layer:
- **Option A (MI Command)**: Add custom MI commands to OpenSIPS (`subscriber_create`, `subscriber_update`, `subscriber_delete`). OpenSIPS uses `sql_query` module to write to PostgreSQL. OCP calls MI HTTP.
- **Option B (REST API)**: Create a thin microservice (e.g., `tsisip-admin-api`) that exposes authenticated endpoints. OCP calls REST API; service uses PDO to write to PostgreSQL.

Decision criteria: alignment with constitution (Option A keeps data access in OpenSIPS layer), complexity (Option B adds a new service), and maintainability.

### AD-2: HA1 Generation Stays in OCP
The `generateHa1Hashes()` function remains in OCP because:
- OCP already has the user's plaintext password during creation/change
- Moving HA1 generation to the proxy would require sending plaintext passwords over the wire
- Precomputed hashes are the contract between OCP and proxy

### AD-3: Proxy Authentication via Internal Network + Shared Secret
OCP and proxy communicate over the internal `sip_internal` or `db_internal` Docker network. Authentication uses a shared service secret (Docker secret) rather than per-user credentials, because both services are project-owned and co-located.

## References

- `.specify/memory/architecture_constitution.md` (ARCH-PRE-001)
- `web/subscribers.php` (file to be refactored)
- `web/common/ha1-generator.php` (HA1 generation utility)
- `docs/TSiSIP-CANONICAL-SPEC.md` (Section 8: Routing Logic)
- Feature 020: `specs/020-ocp-critical-tool-gap-closure/post-implementation-review.md`
