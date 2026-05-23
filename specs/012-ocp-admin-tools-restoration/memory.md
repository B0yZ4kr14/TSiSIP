# Feature 012: OCP Administrative Tools Restoration — Memory

## Current Scope

Feature 012 restores three critical administrative tools to the TSiSIP OCP:

1. **Subscriber Management** (`web/subscribers.php`) — Full CRUD on the `subscriber` table with tenant scoping, HA1 hash generation, pagination (25/page), and soft-disable via the `enabled` flag. No hard-delete capability is provided.
2. **CDR Viewer** (`web/cdr-viewer.php`) — Read-only filtered query interface on the `cdr` table with date range, tenant, call status, from_user, and SIP method filters. Pagination is included but pagination URLs in filtered views have a known query-string concatenation issue.
3. **Dispatcher Management** (`web/dispatcher.php`) — Full CRUD on the `dispatcher` table with add, edit, delete, and state toggle (0=active, 1=inactive). Set ID grouping and priority/weight/attrs editing are supported.

Shared helpers introduced or reused:
- `web/common/ha1-generator.php` — Generates HA1 (MD5), HA1-SHA256, and HA1-SHA512/256 hashes.
- `web/common/csrf.php` — CSRF token generation, validation, and hidden input rendering.
- `web/common/pagination.php` — Reusable LIMIT/OFFSET pagination logic and nav rendering.

## Relevant Decisions

- **AD-1: Server-Side Rendering (PHP)** — All admin tools are traditional PHP forms. No SPA framework was introduced, maintaining consistency with the existing OCP architecture.
- **AD-2: No MI Interface (Deferred)** — Dispatcher changes are DB-backed only. OpenSIPS reloads via existing mechanisms (e.g., `opensips-cli -x mi dispatcher_reload`). No direct MI/FIFO/jsonrpc integration was built.
- **AD-3: Read-Only CDR** — CDR viewer is intentionally read-only. No CDR deletion or modification via GUI.
- **AD-4: Tenant Scoping** — Subscriber management respects `tenant_id`. Admin sees all tenants; devops sees tenants linked to their accessible dispatcher sets (currently hardcoded to default tenant for devops).
- **AD-5: Soft-Disable Only for Subscribers** — Subscriber records are never deleted via GUI; only toggled via `enabled`. This preserves referential integrity and audit trails.
- **AD-6: Password Stored as HA1 Only** — The `password` column in `subscriber` is intentionally set to `''` on create/update; only HA1 hash columns are populated.

## Active Architecture Constraints

- OpenSIPS 3.6 LTS is the only SIP proxy baseline.
- PostgreSQL is the only database. All queries use PDO prepared statements (`PDO::ATTR_EMULATE_PREPARES => false`).
- Docker-first delivery: OCP runs inside a project-owned Docker image (`docker/ocp/Dockerfile`).
- Role hierarchy (lowest to highest): `readonly` -> `user` -> `assistant` -> `dentist` -> `devops` -> `admin`.
- All admin pages must call `requireAuth()` and `checkPasswordChange()` before any business logic.
- Secrets are read from `/tmp` (copied by entrypoint) or `/run/secrets` (Docker secrets), never committed.

## Accepted Deviations

- **Pagination URL bug in CDR viewer**: `renderPagination()` unconditionally appends `?page=N` to the base URL. When `cdr-viewer.php` passes a base URL that already contains query parameters (`cdr-viewer.php?from=...&to=...`), the resulting URL is malformed (`...?to=...?page=2`). This is a known UI bug but does not prevent the date-range filter from functioning. Fix deferred.
- **Devops tenant scoping is coarse**: Non-admin devops users are currently scoped to the default tenant UUID only. Fine-grained dispatcher-set-based tenant access is not yet implemented.
- **No server-side sorting**: Tables are rendered in default query order (`ORDER BY id DESC` or `ORDER BY setid, priority DESC`). No interactive column sorting is provided.
- **Inline edit forms use display:none**: Edit rows are toggled via inline JavaScript (`onclick` to set `display='block'` or `display='none'`). No separate edit page.

## Relevant Security Constraints

| ID | Requirement | Verification |
|---|---|---|
| R1 | All DB queries use PDO prepared statements | Verified in all three pages: `$pdo->prepare(...)` with bound parameters. |
| R2 | Subscriber passwords stored as HA1 hashes only | Verified: `password` column set to `''` on insert/update; only HA1 columns populated. |
| R3 | HA1 (MD5), HA1-SHA256, HA1-SHA512/256 precomputed on create/update | Verified: `generateHa1Hashes()` produces all three hashes. |
| R4 | Admin and devops roles only for system tools | Verified: `requireRole('devops')` on all three pages; admin (level 5) and devops (level 4) both pass. |
| R5 | CSRF tokens on all mutating forms (POST) | Verified: every POST form in `subscribers.php` and `dispatcher.php` includes `csrfInput()`. `cdr-viewer.php` uses GET for non-mutating filters. |
| R6 | Input validation on all user-supplied fields | Verified: required fields checked, password minlength 8, destination must start with `sip:`, numeric casts on dispatcher fields. |
| R7 | `checkPasswordChange()` and `requireAuth()` guards on all admin pages | Verified: both called on all three pages before any data mutation or display. |

## Related Historical Lessons

- **Feature 002 (OCP Rebrand)** applied the TSiSIP visual theme but removed 90%+ of OCP v9 administrative tools. This created an operational gap where SIP users could not be provisioned without direct SQL access.
- **Feature 010 (Navigation System Links)** established the role-based sidebar. The restored tools must integrate with `web/common/role-nav.php`.
- **Feature 011 (Forced Password Change)** established the `checkPasswordChange()` guard. All restored admin pages correctly include it.
- **Stock schema first, then ALTER**: The `subscriber` and `dispatcher` tables use the stock OpenSIPS 3.6 schema. No custom `CREATE TABLE` was introduced, avoiding schema drift.

## Conflict Warnings

- **Pagination helper vs. filtered views**: `renderPagination()` in `web/common/pagination.php` is not query-string-aware. Any future filtered list page will hit the same URL-concatenation bug as `cdr-viewer.php`. If fixing this, update `pagination.php` to accept an associative array of preserved query parameters instead of a raw base URL string.
- **Tenant scoping vs. dispatcher access**: The current devops tenant filter in `subscribers.php` is a placeholder (`tenant_id = '00000000-0000-0000-0000-000000000000'`). If dispatcher-set-to-tenant mapping is added later, the subscriber scoping logic must be updated to match.
- **HA1 generation vs. password changes**: When a subscriber's password is updated, `generateHa1Hashes()` uses the *new* password. This is correct, but note that the `username` and `domain` fields are also editable. If username or domain is changed without a password change, the HA1 hashes are NOT regenerated in the "leave blank to keep" path. This could lead to stale HA1 hashes if username/domain is edited independently. This is a latent bug.

## Retrieval Notes

- Key implementation files:
  - `web/subscribers.php`
  - `web/cdr-viewer.php`
  - `web/dispatcher.php`
  - `web/common/ha1-generator.php`
  - `web/common/csrf.php`
  - `web/common/pagination.php`
  - `web/common/config.php` (role hierarchy, auth guards)
- Schema dependencies: `subscriber` (stock OpenSIPS 3.6), `dispatcher` (stock OpenSIPS 3.6), `cdr` (TSiSIP extension), `tenants` (TSiSIP extension).
- When revisiting this feature, check `renderPagination()` compatibility with GET-filtered pages before adding new list views.
