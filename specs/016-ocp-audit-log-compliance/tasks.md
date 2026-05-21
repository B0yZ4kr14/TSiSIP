# Feature 016 Tasks

## Wave 1: Database Schema & Migrations

- [x] T1.1: Create db/init/04-ocp-audit-schema.sql with ocp_audit_log CREATE TABLE IF NOT EXISTS DDL
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: none

- [x] T1.2: Add performance indexes on ocp_audit_log (event_time, user_id+event_time, action+event_time, resource_type+resource_id+event_time, ip_address+event_time, GIN on details)
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: T1.1

- [x] T1.3: Create fn_ocp_audit_log_immutable() and attach ocp_audit_log_immutable BEFORE UPDATE OR DELETE trigger
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: T1.1

- [x] T1.4: Create ocp_audit_log_retention_purge(retention_days INTEGER) function returning deleted row count
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: T1.1

- [x] T1.5: Add GRANT / REVOKE statements for opensips (INSERT, SELECT, SEQUENCE usage) and tsisip_retention (DELETE bypass via trigger role check)
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: T1.3, T1.4

## Wave 2: Core Audit Library

- [x] T2.1: Create web/common/audit.php with logAuditEvent(string action, ?string resourceType = null, ?string resourceId = null, bool success = true, ?array details = null): void
  - Files: web/common/audit.php (new)
  - Dependencies: T1.1

- [x] T2.2: Implement SHA-256 hash chain inside logAuditEvent() — query last row hash, compute canonical concatenation hash, insert with prev_hash and hash
  - Files: web/common/audit.php
  - Dependencies: T2.1

- [x] T2.3: Implement proxy-aware IP (HTTP_X_FORWARDED_FOR then REMOTE_ADDR), input truncation to column limits, and resilient error handling (catch-all -> error_log(), no throw)
  - Files: web/common/audit.php
  - Dependencies: T2.1

- [x] T2.4: Add verifyAuditLogIntegrity(): array offline validator in web/common/audit.php
  - Files: web/common/audit.php
  - Dependencies: T2.2

- [x] T2.5: Update web/common/config.php to require_once __DIR__ . /audit.php after existing requires
  - Files: web/common/config.php
  - Dependencies: T2.1

- [x] T2.6: Augment authenticateUser() in web/common/config.php to call logAuditEvent(LOGIN, ocp_user, username, true) on success and logAuditEvent(LOGIN, ocp_user, username, false, [reason => reason]) on failure
  - Files: web/common/config.php
  - Dependencies: T2.5

## Wave 3: OCP Integration — Instrument Existing Pages

- [x] T3.1: Instrument web/logout.php — call logAuditEvent(LOGOUT, ocp_user, session username or unknown, true) before clearing session
  - Files: web/logout.php
  - Dependencies: T2.5

- [x] T3.2: Instrument web/change-password.php — call logAuditEvent(PASSWORD_CHANGE, ocp_user, session username, true) on success and logAuditEvent(PASSWORD_CHANGE, ocp_user, session username, false, [reason => error]) on failure
  - Files: web/change-password.php
  - Dependencies: T2.5

- [x] T3.3: Instrument web/subscribers.php create action — emit SUBSCRIBER_CREATE with resource_type=subscriber, resource_id=username, details containing domain, tenant_id, enabled (exclude passwords and HA1)
  - Files: web/subscribers.php
  - Dependencies: T2.5

- [x] T3.4: Instrument web/subscribers.php update action — emit SUBSCRIBER_UPDATE with same safe detail rules
  - Files: web/subscribers.php
  - Dependencies: T2.5

- [x] T3.5: Instrument web/subscribers.php toggle action — emit SUBSCRIBER_TOGGLE with resource_id=id and toggle state in details
  - Files: web/subscribers.php
  - Dependencies: T2.5

- [x] T3.6: Instrument web/dispatcher.php — emit DISPATCHER_CREATE, DISPATCHER_UPDATE, DISPATCHER_DELETE, DISPATCHER_TOGGLE on respective POST actions with resource_type=dispatcher and relevant metadata
  - Files: web/dispatcher.php
  - Dependencies: T2.5

## Wave 4: Compliance Dashboard

- [x] T4.1: Create web/audit-log.php with auth guards (requireAuth, checkPasswordChange, requireRole devops), include common header, footer, role-nav, page title Audit Log & Compliance
  - Files: web/audit-log.php (new)
  - Dependencies: T2.5

- [x] T4.2: Build server-side filter form in web/audit-log.php: from, to, action dropdown, username text, resource_type dropdown, success dropdown, ip_address text, q full-text against details::text
  - Files: web/audit-log.php
  - Dependencies: T4.1

- [x] T4.3: Implement parameterized count query and paginated result query (event_time DESC, default perPage 50, max 200)
  - Files: web/audit-log.php
  - Dependencies: T4.2

- [x] T4.4: Render result table with columns: Event Time, User, Action, Resource, Resource ID, IP Address, Success badge (green/red), Details toggle
  - Files: web/audit-log.php
  - Dependencies: T4.3

- [x] T4.5: Add export toolbar with Export CSV and Export JSON buttons submitting same filters to audit-export.php
  - Files: web/audit-log.php
  - Dependencies: T4.4

- [x] T4.6: Update web/common/role-nav.php — add Audit Log item under Administration section for admin/devops, pointing to audit-log.php
  - Files: web/common/role-nav.php
  - Dependencies: T4.1

- [x] T4.7: Update web/dashboard.php — add Audit Log & Compliance link in System Management for admin/devops
  - Files: web/dashboard.php
  - Dependencies: T4.1

- [x] T4.8: Update web/common/pagination.php — raise perPage ceiling from 100 to 200
  - Files: web/common/pagination.php
  - Dependencies: none

## Wave 5: Export & Retention

- [x] T5.1: Create web/audit-export.php CSV stream: proper headers, UTF-8 BOM, fputcsv() to php://output, columns per spec AC4, no pagination limit
  - Files: web/audit-export.php (new)
  - Dependencies: T2.5, T4.2

- [x] T5.2: Create web/audit-export.php JSON stream: proper headers, output array of objects, stream rows to avoid memory exhaustion
  - Files: web/audit-export.php
  - Dependencies: T5.1

- [x] T5.3: Log EXPORT_CSV or EXPORT_JSON audit event after headers sent, before stream ends, wrapped in try/catch so export is never blocked
  - Files: web/audit-export.php
  - Dependencies: T5.1, T5.2

- [x] T5.4: Create web/cli/purge-audit-log.php — CLI-only guard, reads OCP_AUDIT_RETENTION_DAYS env with fallback 90, logs RETENTION_RUN, calls ocp_audit_log_retention_purge via PDO, prints count, exits 0/1
  - Files: web/cli/purge-audit-log.php (new)
  - Dependencies: T2.5

- [x] T5.5: Update docker/ocp/entrypoint.sh — export OCP_AUDIT_RETENTION_DAYS (default 90) before exec apache2-foreground so mod_php inherits it
  - Files: docker/ocp/entrypoint.sh
  - Dependencies: none

- [x] T5.6: Update docker/ocp/Dockerfile — install cron, create /var/log/tsisip owned by www-data, add daily cron job at 03:17 for audit-retention.sh
  - Files: docker/ocp/Dockerfile
  - Dependencies: T5.4

- [x] T5.7: Update docker-compose.yml — add OCP_AUDIT_RETENTION_DAYS: 90 under ocp service environment block
  - Files: docker-compose.yml
  - Dependencies: none

## Wave 6: Testing & Validation

- [x] T6.1: Create tests/integration/test-ocp-audit.sh covering PHP CLI insert, immutability trigger, retention purge, hash chain, CSV/JSON export via curl
  - Files: tests/integration/test-ocp-audit.sh (new), tests/integration/test-audit-dashboard.sh (new)
  - Dependencies: T3.1-T3.6, T4.1, T5.4

- [x] T6.2: Run PHP syntax check on all new and modified files via scripts/ci-scan.sh
  - Files: all modified/new PHP files
  - Dependencies: T3.1-T3.6, T4.1, T5.1, T5.2, T5.4

- [x] T6.3: Build OCP Docker image (docker compose build ocp) and verify container health check passes, including audit endpoint
  - Files: docker/ocp/Dockerfile, web/healthcheck-audit.php, docker-compose.yml
  - Dependencies: T5.6

- [x] T6.4: Verify immutability trigger blocks UPDATE and DELETE from application role (opensips); verify retention purge works via SECURITY DEFINER
  - Files: db/init/04-ocp-audit-schema.sql
  - Dependencies: T1.3

- [x] T6.5: Verify hash chain continuity via verifyAuditLogIntegrity() PHP CLI across all rows
  - Files: web/common/audit.php
  - Dependencies: T2.2

- [x] T6.6: Security review — confirm no credential material in details JSONB, all queries use PDO prepared statements, role gates enforced on audit-log.php and audit-export.php; CI scan updated
  - Files: web/common/audit.php, web/audit-log.php, web/audit-export.php, scripts/ci-scan.sh
  - Dependencies: T4.1, T5.1