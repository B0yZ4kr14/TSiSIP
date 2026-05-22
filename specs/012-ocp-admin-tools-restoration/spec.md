# Feature 012: OCP Administrative Tools Restoration

## Overview

The TSiSIP OCP frontend is currently a drastically reduced implementation compared to the OpenSIPS Control Panel v9 baseline. While the rebrand (Feature 002) successfully applied the TSiSIP visual theme, it removed 90%+ of the administrative tools required to operate an OpenSIPS 3.6 LTS edge proxy.

This feature restores the **critical subset** of administrative tools needed for day-to-day TSiSIP operations, maintaining the TSiSIP visual identity while reintroducing functional database-backed management.

## Motivation

Popperian falsification of the premise "TSiSIP OCP is a theme rebrand of OCP v9" has demonstrated:
- No subscriber management (SIP users cannot be provisioned via GUI)
- No CDR viewer (call records require direct SQL access)
- No real dispatcher management (stub page with hard-coded data)
- No MI command interface (no runtime interaction with OpenSIPS)
- No dialplan, domain, or routing rule management

An operator must have GUI access to subscriber CRUD, CDR viewing, and dispatcher management to fulfill the TSiSIP mission as a multi-tenant SIP edge proxy platform.

## Functional Goals

1. **Subscriber Management** (`subscribers.php`) — Full CRUD on the `subscriber` table with tenant scoping, HA1 hash generation, and role-based access.
2. **CDR Viewer** (`cdr-viewer.php`) — Read-only filtered query interface on the `cdr` table with date range, tenant, and call status filters.
3. **Real Dispatcher Management** (`dispatcher.php`) — Replace the current stub with full CRUD on the `dispatcher` table, including set ID grouping and state toggling.
4. **Documentation Update** — Update all canonical docs to reflect the restored capabilities and remaining limitations.

## Deployment & Closure Goals

5. **VPS Deploy** — Build, push, and deploy the updated OCP image to the TSiAPP VPS.
6. **GitHub Commit** — Commit all changes with conventional commits.

## Non-Goals

- Full OCP v9 feature parity (18+ tools) — out of scope.
- Call Center, Clusterer, Dialog live view — out of scope.
- Dynamic Routing, Load Balancer, TLS Management — out of scope.
- RTPengine admin panel (beyond current stub) — out of scope.
- MI Commands interface — deferred to future feature.
- Statistics Monitor with D3.js real-time feeds — deferred.

## Security Requirements

| ID | Requirement |
|---|---|
| R1 | All database queries use PDO prepared statements (`$pdo->prepare(...)`) |
| R2 | Subscriber passwords are stored as HA1 hashes only (never plaintext) |
| R3 | HA1 (MD5), HA1-SHA256, and HA1-SHA512/256 are precomputed on create/update |
| R4 | Admin and devops roles only for system tools (subscriber, dispatcher, CDR) |
| R5 | CSRF tokens on all mutating forms (POST) |
| R6 | Input validation on all user-supplied fields (length, charset, SQL injection guards) |
| R7 | `checkPasswordChange()` and `requireAuth()` guards on all admin pages |

## Database Schema Impact

No schema changes required — uses existing tables:
- `subscriber` (stock OpenSIPS 3.6)
- `dispatcher` (stock OpenSIPS 3.6)
- `cdr` (TSiSIP extension)
- `tenants` (TSiSIP extension — for subscriber tenant scoping)

## Architecture Decisions

### AD-1: Server-Side Rendering (PHP)
Following the existing TSiSIP OCP architecture. No SPA framework introduced.

### AD-2: No MI Interface (Deferred)
Direct MI/FIFO/jsonrpc interaction with OpenSIPS is deferred. All changes are DB-backed; OpenSIPS reloads via existing mechanisms.

### AD-3: Read-Only CDR
CDR viewer is intentionally read-only. No CDR deletion or modification via GUI.

### AD-4: Tenant Scoping
Subscriber management respects `tenant_id`. Admin sees all tenants; devops sees tenants linked to their accessible dispatcher sets.

## Acceptance Criteria

- [ ] AC1: `subscribers.php` lists subscribers with pagination (25/page)
- [ ] AC2: `subscribers.php` supports create with auto HA1 generation
- [ ] AC3: `subscribers.php` supports edit (username, domain, password, tenant, enabled)
- [ ] AC4: `subscribers.php` supports soft-disable (not delete) via `enabled` flag
- [ ] AC5: `cdr-viewer.php` lists CDRs with date range filter
- [ ] AC6: `cdr-viewer.php` supports filtering by tenant, call status, and from_user
- [ ] AC7: `dispatcher.php` lists real dispatcher destinations from PostgreSQL
- [ ] AC8: `dispatcher.php` supports add/edit/delete of dispatcher destinations
- [ ] AC9: `dispatcher.php` supports toggle of `state` column (0=active, 1=inactive)
- [ ] AC10: All pages enforce role-based access (admin/devops only)
- [ ] AC11: CSRF tokens present on all mutating forms
- [x] AC12: Deployed to VPS and validated
- [x] AC13: Committed to GitHub with conventional commits

## References

- `reports/ocp-popperian-audit-2026-05-20.md` — Popperian audit that triggered this feature
- Feature 010: `specs/010-ocp-navigation-system-links/` — Navigation structure
- Feature 011: `specs/011-ocp-forced-password-change/` — Auth layer
- OpenSIPS Control Panel v9 docs: https://controlpanel.opensips.org/
- OMK Goal: `feature-012-ocp-administrative-tools-res-2026-05-20T05-45-11-810Z`
