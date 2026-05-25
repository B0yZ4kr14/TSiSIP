# Blueprint — OCP Audit Log & Compliance Dashboard

## Overview

Build an immutable audit logging subsystem for the OCP that captures every significant admin action into a PostgreSQL-backed append-only log, provides a searchable compliance dashboard, supports CSV/JSON export, enforces configurable retention, and integrates transparently with existing PHP auth and role guards.

## Requirements

- **AC1**: PostgreSQL schema `ocp_audit_log` with columns: `id`, `event_time`, `user_id`, `username`, `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `success`, `details` (JSONB), `prev_hash`, `hash`.
  - Six performance indexes including GIN on `details`.
  - `BEFORE UPDATE OR DELETE` trigger `ocp_audit_log_immutable` blocks mutations except by `tsisip_retention` role.
  - `ocp_audit_log_retention_purge(retention_days INTEGER)` function.
- **AC2**: PHP `web/common/audit.php` with `logAuditEvent()` — auto-populates session data, proxy-aware IP, SHA-256 hash chain, resilient error handling.
  - Instrument `authenticateUser()`, `logout.php`, `change-password.php`, `subscribers.php`, `dispatcher.php`.
- **AC3**: Compliance dashboard `web/audit-log.php` — date-range, action, username, resource_type, success, IP, full-text `q` filters; paginated (default 50, max 200); export toolbar.
- **AC4**: Export endpoint `web/audit-export.php` — CSV (UTF-8 BOM, `fputcsv`) and JSON streaming; logs export audit event.
- **AC5**: Retention policy — `OCP_AUDIT_RETENTION_DAYS` (default 90); daily cron at 03:17 via `web/cli/purge-audit-log.php`.
- **AC6**: Immutability & tamper evidence — trigger blocks UPDATE/DELETE; hash chain validated offline via `verifyAuditLogIntegrity()`.
- **AC7**: Role-based access — `requireRole('devops')`; nav in `role-nav.php` and `dashboard.php`.
- **AC8**: Docker-first — OCP Dockerfile creates `/var/log/tsisip`, installs cron.

## Architecture

- **Stack**: PHP 8.2 + Apache, PostgreSQL 16, Docker Compose.
- **Audit Library**: `web/common/audit.php` — hash chain, proxy-aware IP, input truncation, catch-all error handling.
- **Schema**: `db/init/04-ocp-audit-schema.sql` — idempotent DDL, indexes, trigger, function, grants.
- **Dashboard**: Server-side filtering with parameterized PDO; pagination via `common/pagination.php`.
- **Export**: Streaming CSV/JSON to avoid memory exhaustion.
- **Retention**: CLI-only PHP script; cron inside OCP container.
- **Immutability**: PostgreSQL row-level trigger + role-based bypass for retention job.

## Implementation Plan

### Wave 1: Database Schema & Migrations
- Append `ocp_audit_log` DDL to `db/init/04-ocp-audit-schema.sql`.
- Add six performance indexes.
- Create immutability trigger and retention purge function.
- Add role privilege grants.

### Wave 2: Core Audit Library
- Create `web/common/audit.php` with `logAuditEvent()`.
- Implement SHA-256 hash chain.
- Proxy-aware IP, input truncation, resilient error handling.
- Create `verifyAuditLogIntegrity()` offline validator.
- Update `web/common/config.php` to require audit library.
- Augment `authenticateUser()` to emit LOGIN audit events.

### Wave 3: OCP Integration — Instrument Existing Pages
- Instrument `logout.php`, `change-password.php`, `subscribers.php`, `dispatcher.php`.

### Wave 4: Compliance Dashboard
- Create `web/audit-log.php` with auth guards and filter form.
- Parameterized count + paginated query.
- Render result table with success badges and details toggle.
- Add export toolbar.
- Update `role-nav.php` and `dashboard.php`.

### Wave 5: Export & Retention
- Create `web/audit-export.php` (CSV and JSON).
- Log export audit events.
- Create `web/cli/purge-audit-log.php`.
- Update `docker/ocp/entrypoint.sh` and Dockerfile for cron.
- Update `docker-compose.yml` with retention env var.

### Wave 6: Testing & Validation
- Create integration test script.
- Run PHP syntax check on all files.
- Build OCP image and verify health check.
- Verify immutability trigger and hash chain continuity.
- Security review.

## Tasks

**Wave 1: Database Schema & Migrations**
- T1.1: Create `db/init/04-ocp-audit-schema.sql`
- T1.2: Add performance indexes
- T1.3: Create immutability trigger
- T1.4: Create retention purge function
- T1.5: Add role privilege grants

**Wave 2: Core Audit Library**
- T2.1: Create `web/common/audit.php`
- T2.2: Implement SHA-256 hash chain
- T2.3: Proxy-aware IP, truncation, resilient error handling
- T2.4: Add `verifyAuditLogIntegrity()`
- T2.5: Update `web/common/config.php`
- T2.6: Augment `authenticateUser()`

**Wave 3: OCP Integration**
- T3.1: Instrument `logout.php`
- T3.2: Instrument `change-password.php`
- T3.3: Instrument `subscribers.php` (create)
- T3.4: Instrument `subscribers.php` (update)
- T3.5: Instrument `subscribers.php` (toggle)
- T3.6: Instrument `dispatcher.php`

**Wave 4: Compliance Dashboard**
- T4.1: Create `web/audit-log.php`
- T4.2: Build server-side filter form
- T4.3: Parameterized count + paginated query
- T4.4: Render result table
- T4.5: Add export toolbar
- T4.6: Update `role-nav.php`
- T4.7: Update `dashboard.php`
- T4.8: Update `pagination.php` ceiling to 200

**Wave 5: Export & Retention**
- T5.1: Create `web/audit-export.php` CSV
- T5.2: Create `web/audit-export.php` JSON
- T5.3: Log export audit events
- T5.4: Create `web/cli/purge-audit-log.php`
- T5.5: Update `docker/ocp/entrypoint.sh`
- T5.6: Update `docker/ocp/Dockerfile`
- T5.7: Update `docker-compose.yml`

**Wave 6: Testing & Validation**
- T6.1: Create integration test script
- T6.2: Run PHP syntax check
- T6.3: Build OCP image and verify health
- T6.4: Verify immutability trigger
- T6.5: Verify hash chain continuity
- T6.6: Security review

## Validation

- Manual test covers login, subscriber CRUD, password change, logout.
- Direct SQL `UPDATE ocp_audit_log SET ...` rejected by trigger.
- Hash chain continuous: no orphaned `prev_hash` except first row; each matches previous row's `hash`.
- All PHP files pass `php -l` syntax validation.
- OCP container builds and starts; health check passes.
- Export produces valid CSV/JSON with correct headers.
- Retention purge runs via cron and deletes only expired rows.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Audit logging latency impacts page load | Async insert with catch-all; no blocking on audit failure |
| Hash chain breaks if rows manually deleted at DB level | Trigger blocks DELETE except by retention role |
| Large export causes memory exhaustion | Stream rows one by one; cap at 10,000 if needed |
| Retention cron job fails silently | Log stdout/stderr to `/var/log/tsisip/audit-retention.log` |
| Credential material leaked into `details` JSONB | Explicit rejection of passwords/HA1 in details payload |

**Dependencies**: Feature 002 (OCP Rebrand); Feature 010 (Navigation); Feature 011 (Forced Password Change); Feature 012 (Admin Tools); PostgreSQL; Docker Compose.
