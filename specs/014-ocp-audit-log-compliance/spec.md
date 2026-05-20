# Feature 014-B: OCP Audit Log & Compliance Dashboard

## Overview

**Feature**: OCP Audit Log & Compliance Dashboard  
**Short name**: ocp-audit-log-compliance  
**Created**: 2026-05-19  
**Status**: Specified (Ready for Implementation)

### Context

The TSiSIP Operator Control Panel (OCP) currently lacks centralized audit logging for administrative actions. While `ocp_login_log` tracks authentication events, there is no record of post-login actions such as subscriber CRUD, dispatcher changes, password resets, or configuration updates. This feature introduces a comprehensive, append-only audit trail with a searchable compliance dashboard, data export capabilities, configurable retention, and optional tamper-evident hashing.

### Objective

Build an immutable audit logging subsystem for the OCP that:
1. Captures every significant admin action into a PostgreSQL-backed append-only log.
2. Provides a searchable, filterable compliance dashboard accessible to Admin and DevOps roles.
3. Supports CSV and JSON export for external compliance workflows.
4. Enforces a configurable retention policy (default 90 days) via an automated purge mechanism.
5. Integrates transparently with the existing PHP auth flow, role guards, and page structure.

---

## Goals

1. **Comprehensive Action Capture**: Log all mutating and security-relevant actions across the OCP, including login, logout, password changes, subscriber CRUD, dispatcher CRUD, and any future admin tools.
2. **Compliance Dashboard**: Deliver a dedicated `audit-log.php` page with date-range filtering, full-text search, role-based access control, and paginated results.
3. **Data Portability**: Enable one-click export of filtered results to CSV and JSON.
4. **Retention Governance**: Automatically purge audit records older than a configurable threshold while preserving log integrity during the retention window.
5. **Immutability Guarantee**: Ensure audit entries are append-only; no application-level UPDATE or DELETE paths exist except through the retention purge job.
6. **Tamper Evidence (Optional)**: Provide a SHA-256 hash chain column that can be validated offline to detect tampering.

## Non-Goals

1. Real-time alerting or SIEM integration (out of scope; may be addressed in a future observability feature).
2. Audit logging of SIP-level traffic (already covered by `auth_audit_log` and CDR tables).
3. Digital signatures or blockchain anchoring.
4. Rewriting the existing `ocp_login_log` table (this feature extends it, does not replace it).
5. MySQL/MariaDB support.
6. Bare-metal or VM-first deployment instructions.

---

## Acceptance Criteria

### AC1: PostgreSQL Schema — `ocp_audit_log`

- [ ] A new table `ocp_audit_log` is created in `db/init/02-tsisip-extensions.sql` (or a new idempotent migration script) with the following exact columns:

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | `BIGSERIAL` | `PRIMARY KEY` | Surrogate key (not exposed in hash chain) |
| `event_time` | `TIMESTAMPTZ` | `NOT NULL DEFAULT NOW()` | Timestamp of the action |
| `user_id` | `UUID` | `NULL` | FK to `ocp_users.id`; NULL for unauthenticated attempts (e.g., failed login) |
| `username` | `VARCHAR(64)` | `NOT NULL` | Denormalized username for audit resilience if user row is ever removed |
| `action` | `VARCHAR(64)` | `NOT NULL` | Canonical action code: `LOGIN`, `LOGOUT`, `PASSWORD_CHANGE`, `SUBSCRIBER_CREATE`, `SUBSCRIBER_UPDATE`, `SUBSCRIBER_TOGGLE`, `DISPATCHER_CREATE`, `DISPATCHER_UPDATE`, `DISPATCHER_DELETE`, `DISPATCHER_TOGGLE`, `CONFIG_VIEW`, `EXPORT_CSV`, `EXPORT_JSON`, `RETENTION_RUN` |
| `resource_type` | `VARCHAR(64)` | `NULL` | Domain entity: `subscriber`, `dispatcher`, `ocp_user`, `audit_log`, `system` |
| `resource_id` | `VARCHAR(255)` | `NULL` | Primary key or identifier of the affected resource |
| `ip_address` | `INET` | `NOT NULL` | Client IP address |
| `user_agent` | `VARCHAR(512)` | `NULL` | Client user-agent string |
| `success` | `BOOLEAN` | `NOT NULL DEFAULT TRUE` | Whether the action completed successfully |
| `details` | `JSONB` | `NULL` | Structured payload (e.g., old/new values for updates, filter params for exports) |
| `prev_hash` | `VARCHAR(64)` | `NULL` | Hex-encoded SHA-256 of the previous row's `hash` column (NULL for first row) |
| `hash` | `VARCHAR(64)` | `NOT NULL` | Hex-encoded SHA-256 of the canonical concatenation of all row fields plus `prev_hash` |

- [ ] The following indexes are created:
  - `idx_ocp_audit_event_time ON ocp_audit_log(event_time)`
  - `idx_ocp_audit_user_id ON ocp_audit_log(user_id, event_time)`
  - `idx_ocp_audit_action ON ocp_audit_log(action, event_time)`
  - `idx_ocp_audit_resource ON ocp_audit_log(resource_type, resource_id, event_time)`
  - `idx_ocp_audit_ip ON ocp_audit_log(ip_address, event_time)`
  - `idx_ocp_audit_details_gin ON ocp_audit_log USING GIN(details)` (for JSONB filtering)

- [ ] A PostgreSQL `BEFORE DELETE` and `BEFORE UPDATE` row-level trigger named `ocp_audit_log_immutable` prevents any `UPDATE` or `DELETE` on `ocp_audit_log` except when executed by a specific service role (`tsisip_retention`). The trigger function must raise an exception with the message `'Audit log entries are immutable'`.

- [ ] A PostgreSQL function `ocp_audit_log_retention_purge(retention_days INTEGER)` is created that:
  - Accepts a retention period in days.
  - Deletes rows where `event_time < NOW() - INTERVAL '1 day' * retention_days`.
  - Can only be executed by the `tsisip_retention` role or a superuser.
  - Returns the count of deleted rows.

- [ ] The schema must be idempotent (use `CREATE TABLE IF NOT EXISTS`, `CREATE OR REPLACE FUNCTION`, `DROP TRIGGER IF EXISTS` + `CREATE TRIGGER`, etc.).

### AC2: PHP Audit Middleware / Hook

- [ ] A new file `web/common/audit.php` is created containing:
  - `function logAuditEvent(string $action, ?string $resourceType = null, ?string $resourceId = null, bool $success = true, ?array $details = null): void`
  - The function auto-populates `user_id`, `username`, `ip_address`, and `user_agent` from the current session/`$_SERVER`.
  - `ip_address` must respect reverse-proxies: read `HTTP_X_FORWARDED_FOR` first, then `REMOTE_ADDR`, falling back to `'0.0.0.0'`.
  - If no session exists (unauthenticated context), `user_id` is `NULL` and `username` is `'anonymous'`.
  - The function computes the SHA-256 hash chain by querying `SELECT hash FROM ocp_audit_log ORDER BY id DESC LIMIT 1` and using that as `prev_hash`.
  - The function is resilient to DB failures: any exception during logging is caught, written to `error_log()`, and **must not** propagate or disrupt the user-facing operation.
  - All string inputs are truncated to their column max lengths before insertion.

- [ ] `web/common/config.php` is updated to `require_once __DIR__ . '/audit.php';` so `logAuditEvent()` is available on every page.

- [ ] `authenticateUser()` in `web/common/config.php` is modified to call `logAuditEvent('LOGIN', 'ocp_user', $username, true)` on success and `logAuditEvent('LOGIN', 'ocp_user', $username, false, ['reason' => $reason])` on failure (replacing or augmenting the existing `logLoginAttempt()` — both tables may be written for backward compatibility).

- [ ] `web/logout.php` calls `logAuditEvent('LOGOUT', 'ocp_user', $_SESSION['ocp_username'] ?? 'unknown', true)` before destroying the session.

- [ ] `web/change-password.php` calls `logAuditEvent('PASSWORD_CHANGE', 'ocp_user', $_SESSION['ocp_username'], true)` on successful password update and `logAuditEvent('PASSWORD_CHANGE', 'ocp_user', $_SESSION['ocp_username'], false, ['reason' => $error])` on failure.

- [ ] `web/subscribers.php` calls `logAuditEvent()` for every mutating POST action (`SUBSCRIBER_CREATE`, `SUBSCRIBER_UPDATE`, `SUBSCRIBER_TOGGLE`) with:
  - `resource_type = 'subscriber'`
  - `resource_id = $username` (or the subscriber ID for toggle)
  - `details` containing a JSON object with relevant non-sensitive metadata (e.g., `domain`, `tenant_id`, `enabled`). Passwords and HA1 hashes must **never** be written to `details`.

- [ ] `web/dispatcher.php` calls `logAuditEvent()` for every mutating POST action (`DISPATCHER_CREATE`, `DISPATCHER_UPDATE`, `DISPATCHER_DELETE`, `DISPATCHER_TOGGLE`) with:
  - `resource_type = 'dispatcher'`
  - `resource_id = $destination` (or dispatcher ID)
  - `details` containing `setid`, `destination`, `state`, etc.

- [ ] Any future admin page can emit a single `logAuditEvent()` call; no central routing interceptor is required (keeping the PHP codebase simple and debuggable).

### AC3: Compliance Dashboard Page

- [ ] A new file `web/audit-log.php` is created with the following behavior:
  - Requires `requireAuth(); checkPasswordChange(); requireRole('devops');`
  - Includes `common/header.php`, `common/footer.php`, and the standard sidebar via `role-nav.php`.
  - Page title: `<?php echo _('Audit Log & Compliance'); ?>`

- [ ] The dashboard provides server-side filtering via GET parameters:
  - `from` (date, default: 7 days ago)
  - `to` (date, default: today)
  - `action` (dropdown of canonical action codes)
  - `username` (text search, partial match allowed)
  - `resource_type` (dropdown)
  - `success` (dropdown: all / success / failure)
  - `ip_address` (text search, partial match allowed)
  - `q` (full-text search against `details` JSONB using `details::text ILIKE`)
  - `page` (integer, pagination)

- [ ] Query requirements:
  - Results are ordered by `event_time DESC`.
  - Pagination uses the existing `common/pagination.php` helpers (`getPagination()`, `renderPagination()`).
  - Default `perPage` is 50; max allowed `perPage` is 200.
  - The SQL query must use parameterized PDO statements for all filters.
  - Count query runs first for pagination.

- [ ] Result table columns displayed:
  - `Event Time` (formatted as `Y-m-d H:i:s T`)
  - `User`
  - `Action`
  - `Resource`
  - `Resource ID`
  - `IP Address`
  - `Success` (green badge for true, red badge for false)
  - `Details` (collapsible/expandable JSON view or a `tsisip-btn-secondary` toggle to show raw JSON in a `<pre>` block)

- [ ] The page includes an export toolbar with two buttons:
  - `Export CSV` — submits the same filters to `audit-export.php?format=csv`
  - `Export JSON` — submits the same filters to `audit-export.php?format=json`

- [ ] If no results match the filters, display: `<p class="tsisip-text-muted tsisip-text-center"><?php echo _('No audit events found.'); ?></p>`

### AC4: Export Endpoint (`audit-export.php`)

- [ ] A new file `web/audit-export.php` is created:
  - Requires `requireAuth(); checkPasswordChange(); requireRole('devops');`
  - Accepts `format` (csv|json) and the same filter parameters as `audit-log.php`.
  - Applies identical filter logic and query construction.
  - **No pagination limit** on export; exports the full filtered result set.
  - Stream output to avoid memory exhaustion on large result sets (use PDO `PDO::CURSOR_SCROLL` or unbuffered query with `setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false)` equivalent for pgsql; if unbuffered is problematic, cap export at 10,000 rows and document it).

- [ ] CSV export requirements:
  - HTTP headers: `Content-Type: text/csv; charset=utf-8`, `Content-Disposition: attachment; filename="tsisip-audit-YYYY-MM-DD.csv"`
  - UTF-8 BOM (`\xEF\xBB\xBF`) prepended for Excel compatibility.
  - Columns: `event_time`, `username`, `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `success`, `details` (flattened as JSON string), `hash`
  - Proper CSV escaping using `fputcsv()` to a `php://output` stream.
  - Log an `EXPORT_CSV` audit event with `details = ['filter_count' => $totalItems]` upon completion.

- [ ] JSON export requirements:
  - HTTP headers: `Content-Type: application/json; charset=utf-8`, `Content-Disposition: attachment; filename="tsisip-audit-YYYY-MM-DD.json"`
  - Output a JSON array of objects; stream rows one by one to avoid memory issues.
  - Log an `EXPORT_JSON` audit event with `details = ['filter_count' => $totalItems]` upon completion.

- [ ] Both exports must record the `logAuditEvent()` call **after** the HTTP headers are sent and **before** stream termination, wrapped in a try/catch so export delivery is never blocked by audit logging.

### AC5: Retention Policy & Automated Purge

- [ ] The retention period is configurable via an environment variable `OCP_AUDIT_RETENTION_DAYS` with a default value of `90`.

- [ ] The OCP container entrypoint (`docker/ocp/entrypoint.sh`) exports `OCP_AUDIT_RETENTION_DAYS` to PHP-FPM by writing it to `/etc/php/8.2/fpm/pool.d/env.conf` (or the equivalent PHP-FPM environment pass-through mechanism).

- [ ] A standalone PHP CLI script `web/cli/purge-audit-log.php` is created:
  - Must be executed from the command line only (`php_sapi_name() === 'cli'` or die).
  - Reads `OCP_AUDIT_RETENTION_DAYS` from environment; falls back to `90`.
  - Calls `logAuditEvent('RETENTION_RUN', 'audit_log', 'system', true, ['retention_days' => $days])` before purging.
  - Calls `SELECT ocp_audit_log_retention_purge(:days)` via PDO.
  - Prints the number of deleted rows to STDOUT and exits with code `0` on success.
  - Exits with code `1` on failure and prints error to STDERR.

- [ ] A cron job is configured inside the `docker/ocp/Dockerfile` (or via the host crontab documented in the runbook) to run `purge-audit-log.php` daily at `03:17` local time. The cron entry must redirect stdout/stderr to a log file:
  ```
  17 3 * * * cd /var/www/html && /usr/bin/php web/cli/purge-audit-log.php >> /var/log/tsisip/audit-retention.log 2>&1
  ```

- [ ] The directory `/var/log/tsisip` is created in the OCP Dockerfile with `www-data` ownership.

### AC6: Immutability & Tamper Evidence

- [ ] The PostgreSQL trigger `ocp_audit_log_immutable` (AC1) is verified to block:
  - Any `UPDATE` statement on `ocp_audit_log` from application roles.
  - Any `DELETE` statement on `ocp_audit_log` from application roles.

- [ ] The retention purge bypasses the trigger by executing as the `tsisip_retention` role. The OCP application DB user (`opensips`) does **not** have `DELETE` privileges on `ocp_audit_log`; it only has `INSERT` and `SELECT`.

- [ ] The hash chain column (`prev_hash` -> `hash`) is computed for every inserted row. A PHP helper function `verifyAuditLogIntegrity(): array` is provided in `web/common/audit.php` for offline validation:
  - Iterates rows ordered by `id`.
  - Recomputes `hash` for each row.
  - Returns an array of `{ 'id': int, 'valid': bool, 'expected_hash': string, 'actual_hash': string }`.
  - The function is documented but not exposed in the web UI (to be used by compliance officers via CLI or future feature).

### AC7: Integration with Existing OCP Auth & Role System

- [ ] The `audit-log.php` and `audit-export.php` pages are gated by `requireRole('devops')`, meaning only Admin and DevOps roles can access them.

- [ ] The `role-nav.php` sidebar is updated to include an `Audit Log` link under the `Administration` section for admin and devops roles, pointing to `audit-log.php`.

- [ ] The `dashboard.php` system management section is updated to include a link to `audit-log.php` labeled `Audit Log & Compliance` for admin and devops roles.

- [ ] The existing `$_SESSION` variables (`ocp_user_id`, `ocp_username`, `ocp_user_role`) are used as the source of truth for audit attribution; no parallel auth mechanism is introduced.

### AC8: Docker-First Deployment

- [ ] The OCP Dockerfile (`docker/ocp/Dockerfile`) is updated to:
  - Create `/var/log/tsisip` owned by `www-data`.
  - Install the cron daemon or add the cron job to the system crontab.
  - Ensure the cron service is started by the entrypoint or supervisord.

- [ ] The `docker-compose.yml` `ocp` service section is updated (commented or documented) to show:
  ```yaml
  environment:
    OCP_AUDIT_RETENTION_DAYS: 90
  ```

- [ ] The OCP container health check continues to pass after the changes.

### AC9: Testing & Validation

- [ ] A manual test script `tests/audit-log-validation.php` is created (or documented steps) that:
  1. Logs in as an admin user.
  2. Creates a subscriber and verifies an `SUBSCRIBER_CREATE` row exists in `ocp_audit_log`.
  3. Toggles a subscriber and verifies `SUBSCRIBER_TOGGLE`.
  4. Changes a password and verifies `PASSWORD_CHANGE`.
  5. Logs out and verifies `LOGOUT`.
  6. Attempts an `UPDATE ocp_audit_log SET ...` via direct SQL and confirms it is rejected by the trigger.
  7. Verifies the hash chain is continuous (no `NULL prev_hash` except for the first row, every `prev_hash` matches the preceding row's `hash`).

- [ ] All existing PHP files pass syntax validation:
  ```bash
  find web/ -name "*.php" -exec php -l {} \;
  ```

- [ ] The container builds and starts successfully:
  ```bash
  docker compose build ocp
  docker compose up -d ocp
  docker compose exec ocp bash -c "curl -fsSL http://localhost/login.php | grep -q 'TSiSIP'"
  ```

---

## Rejected Patterns

| Rejected | Canonical Replacement |
|---|---|
| MySQL/MariaDB (`db_mysql`) | PostgreSQL only (`db_postgres`, PDO pgsql DSN) |
| Generic `actions` table without `resource_type`/`resource_id` | Explicit `resource_type` + `resource_id` for traceability |
| `VARCHAR` for `details` or `TEXT` without structure | `JSONB` for structured, queryable metadata |
| Application-level soft-delete (`deleted_at`) for audit rows | PostgreSQL trigger-enforced hard immutability |
| Cron inside a separate sidecar container | Cron inside the OCP container (aligned with existing architecture) |
| Separate audit database or service | Same PostgreSQL instance, separate table, restricted privileges |
| Central PHP request interceptor / auto-prepend file | Explicit `logAuditEvent()` calls per action (simpler, debuggable, no magic) |
| Writing plaintext passwords or HA1 hashes into `details` | Never include credential material in audit metadata |
| `calculate_ha1 = 1` | Not applicable; this is an OCP feature, not an OpenSIPS auth feature |

---

## Database Schema Addendum

### Full `ocp_audit_log` DDL (reference for implementation)

```sql
-- ============================================================
-- Feature 014-B: OCP Audit Log & Compliance Dashboard
-- ============================================================

CREATE TABLE IF NOT EXISTS ocp_audit_log (
    id              BIGSERIAL PRIMARY KEY,
    event_time      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    user_id         UUID,
    username        VARCHAR(64) NOT NULL,
    action          VARCHAR(64) NOT NULL,
    resource_type   VARCHAR(64),
    resource_id     VARCHAR(255),
    ip_address      INET NOT NULL,
    user_agent      VARCHAR(512),
    success         BOOLEAN NOT NULL DEFAULT TRUE,
    details         JSONB,
    prev_hash       VARCHAR(64),
    hash            VARCHAR(64) NOT NULL
);

-- Performance indexes
CREATE INDEX IF NOT EXISTS idx_ocp_audit_event_time
    ON ocp_audit_log(event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_user_id
    ON ocp_audit_log(user_id, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_action
    ON ocp_audit_log(action, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_resource
    ON ocp_audit_log(resource_type, resource_id, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_ip
    ON ocp_audit_log(ip_address, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_details_gin
    ON ocp_audit_log USING GIN(details);

-- Immutability trigger: blocks UPDATE/DELETE unless executed by tsisip_retention role
CREATE OR REPLACE FUNCTION fn_ocp_audit_log_immutable()
RETURNS TRIGGER AS $$
BEGIN
    IF (CURRENT_USER = 'tsisip_retention') THEN
        RETURN OLD;
    END IF;
    RAISE EXCEPTION 'Audit log entries are immutable';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS ocp_audit_log_immutable ON ocp_audit_log;
CREATE TRIGGER ocp_audit_log_immutable
    BEFORE UPDATE OR DELETE ON ocp_audit_log
    FOR EACH ROW
    EXECUTE FUNCTION fn_ocp_audit_log_immutable();

-- Retention purge function
CREATE OR REPLACE FUNCTION ocp_audit_log_retention_purge(retention_days INTEGER)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM ocp_audit_log
    WHERE event_time < NOW() - INTERVAL '1 day' * retention_days;
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- Grant minimal privileges: OCP app user can only INSERT and SELECT
-- (Execute this manually or via init script if the role exists)
-- REVOKE UPDATE, DELETE, TRUNCATE ON ocp_audit_log FROM opensips;
-- GRANT INSERT, SELECT ON ocp_audit_log TO opensips;
-- GRANT USAGE, SELECT ON SEQUENCE ocp_audit_log_id_seq TO opensips;
```

---

## Files to Create / Modify

### New Files
- `web/common/audit.php`
- `web/audit-log.php`
- `web/audit-export.php`
- `web/cli/purge-audit-log.php`
- `tests/audit-log-validation.php` (or manual test documentation)

### Modified Files
- `db/init/02-tsisip-extensions.sql` — add `ocp_audit_log` table, indexes, trigger, function
- `web/common/config.php` — require `audit.php`, augment `authenticateUser()`
- `web/login.php` — no direct change required (handled in `authenticateUser()`)
- `web/logout.php` — add `logAuditEvent('LOGOUT', ...)`
- `web/change-password.php` — add `logAuditEvent('PASSWORD_CHANGE', ...)`
- `web/subscribers.php` — add `logAuditEvent()` calls for create/update/toggle
- `web/dispatcher.php` — add `logAuditEvent()` calls for create/update/delete/toggle
- `web/common/role-nav.php` — add `Audit Log & Compliance` nav item
- `web/dashboard.php` — add `Audit Log & Compliance` system link
- `docker/ocp/Dockerfile` — create log dir, install/configure cron
- `docker/ocp/entrypoint.sh` — pass `OCP_AUDIT_RETENTION_DAYS` to PHP-FPM
- `docker-compose.yml` — document `OCP_AUDIT_RETENTION_DAYS` env var

---

## Related Features

- Feature 002: TSiSIP OCP Rebrand (base UI theme and asset pipeline)
- Feature 010: OCP Navigation System Links (sidebar and dashboard structure)
- Feature 011: OCP Forced Password Change (security event to be audited)
- Feature 012: OCP Admin Tools Restoration (subscriber/dispatcher CRUD pages)

---

## Compliance Notes

- **SOC 2 Type II / ISO 27001**: Immutable audit trails with 90-day retention satisfy common control requirements for change management and access logging.
- **GDPR Art. 5(1)(e)**: Retention limits are enforced automatically; audit data is not kept indefinitely.
- **PCI-DSS v4.0 10.2 / 10.3**: The schema captures user identification, event type, date/time, success/failure, and origination — aligned with PCI log requirements.
- **Hash Chain**: While not a digital signature, the SHA-256 chain provides a lightweight tamper-evident mechanism suitable for internal compliance reviews.
