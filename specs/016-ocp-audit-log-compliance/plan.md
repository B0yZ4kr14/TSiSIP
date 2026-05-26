# Feature 016 Implementation Plan: OCP Audit Log & Compliance Dashboard

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/016-ocp-audit-log-compliance/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



## Wave 1: Database Schema & Migrations (Coder Agent)

Agent: `coder`

- [ ] W1.1: Append `ocp_audit_log` table DDL to `db/init/02-tsisip-extensions.sql` (idempotent, all columns per spec AC1).
- [ ] W1.2: Add six performance indexes including GIN index on `details` JSONB.
- [ ] W1.3: Create immutability trigger function `fn_ocp_audit_log_immutable()` and attach trigger `ocp_audit_log_immutable`.
- [ ] W1.4: Create retention purge function `ocp_audit_log_retention_purge(retention_days INTEGER)`.
- [ ] W1.5: Add role privilege grants (`opensips` -> INSERT/SELECT; `tsisip_retention` -> DELETE bypass).

## Wave 2: Core Audit Library (Coder Agent)

Agent: `coder`
Depends on: W1.

- [ ] W2.1: Create `web/common/audit.php` with `logAuditEvent()` signature matching AC2.
- [ ] W2.2: Implement SHA-256 hash chain computation (`prev_hash` -> `hash`) inside `logAuditEvent()`.
- [ ] W2.3: Implement proxy-aware IP resolution, input truncation, and resilient error handling (catch-all, `error_log()`).
- [ ] W2.4: Create offline integrity validator `verifyAuditLogIntegrity(): array` in `web/common/audit.php`.
- [ ] W2.5: Update `web/common/config.php` to `require_once __DIR__ . '/audit.php'`.
- [ ] W2.6: Augment `authenticateUser()` in `web/common/config.php` to emit `LOGIN` audit events on success and failure.

## Wave 3: OCP Integration — Instrument Existing Pages (Coder Agent)

Agent: `coder`
Depends on: W2.

- [ ] W3.1: Instrument `web/logout.php` — call `logAuditEvent('LOGOUT', ...)` before session destruction.
- [ ] W3.2: Instrument `web/change-password.php` — call `logAuditEvent('PASSWORD_CHANGE', ...)` on success and failure.
- [ ] W3.3: Instrument `web/subscribers.php` — emit `SUBSCRIBER_CREATE` on create success/failure.
- [ ] W3.4: Instrument `web/subscribers.php` — emit `SUBSCRIBER_UPDATE` on update success/failure.
- [ ] W3.5: Instrument `web/subscribers.php` — emit `SUBSCRIBER_TOGGLE` on toggle success/failure.
- [ ] W3.6: Instrument `web/dispatcher.php` — emit `DISPATCHER_CREATE`, `DISPATCHER_UPDATE`, `DISPATCHER_DELETE`, `DISPATCHER_TOGGLE` on respective actions.

## Wave 4: Compliance Dashboard (Coder Agent)

Agent: `coder`
Depends on: W2.

- [ ] W4.1: Create `web/audit-log.php` with `requireAuth(); checkPasswordChange(); requireRole('devops');` and standard page shell.
- [ ] W4.2: Build server-side filter form: date range (`from`/`to`), action dropdown, username text search, resource_type dropdown, success dropdown, IP text search, full-text `q` against `details::text`.
- [ ] W4.3: Implement parameterized count query + paginated result query (`event_time DESC`; `perPage` default 50, max 200).
- [ ] W4.4: Render result table: Event Time, User, Action, Resource, Resource ID, IP Address, Success badge, Details toggle.
- [ ] W4.5: Add export toolbar with CSV and JSON buttons targeting `audit-export.php`.
- [ ] W4.6: Update `web/common/role-nav.php` — add `Audit Log` under Administration for admin/devops.
- [ ] W4.7: Update `web/dashboard.php` — add `Audit Log & Compliance` link in System Management for admin/devops.
- [ ] W4.8: Update `web/common/pagination.php` — raise `perPage` ceiling from 100 to 200 to match spec max.

## Wave 5: Export & Retention (Coder Agent)

Agent: `coder`
Depends on: W2, W4.

- [ ] W5.1: Create `web/audit-export.php` — CSV stream export with UTF-8 BOM, `fputcsv()` to `php://output`, proper headers.
- [ ] W5.2: Create `web/audit-export.php` — JSON stream export (array of objects) with proper headers.
- [ ] W5.3: Log `EXPORT_CSV` / `EXPORT_JSON` audit events after headers are sent, wrapped in try/catch.
- [ ] W5.4: Create `web/cli/purge-audit-log.php` — CLI-only, reads `OCP_AUDIT_RETENTION_DAYS`, logs `RETENTION_RUN`, calls `ocp_audit_log_retention_purge()`.
- [ ] W5.5: Update `docker/ocp/entrypoint.sh` — export `OCP_AUDIT_RETENTION_DAYS` (default 90) before `exec apache2-foreground`.
- [ ] W5.6: Update `docker/ocp/Dockerfile` — install cron, create `/var/log/tsisip` (www-data owned), inject daily cron job at 03:17.
- [ ] W5.7: Update `docker-compose.yml` — add `OCP_AUDIT_RETENTION_DAYS: 90` under `ocp` service `environment`.

## Wave 6: Testing & Validation (QA / Reviewer Agents)

Agent: `qa`, `reviewer`
Depends on: W3, W4, W5.

- [ ] W6.1: Create `tests/audit-log-validation.php` — automated/manual test covering login, subscriber CRUD, password change, logout, and trigger rejection.
- [ ] W6.2: Run `find web/ -name "*.php" -exec php -l {} \;` and fix any syntax errors.
- [ ] W6.3: Build OCP image (`docker compose build ocp`) and verify container health check passes.
- [ ] W6.4: Verify immutability trigger blocks `UPDATE` / `DELETE` from application role.
- [ ] W6.5: Verify hash chain continuity (no orphaned `prev_hash`, each matches previous row's `hash`).
- [ ] W6.6: Security review — confirm no credential material in `details`, parameterized queries only, role gates enforced.

## Dependency Graph

```
W1 (Schema) -> W2 (Audit Lib) -> W3 (Instrument Pages)
                          \\   -> W4 (Dashboard) -> W5 (Export/Retention) -> W6 (QA)
```
