# Feature 016 Memory: OCP Audit Log & Compliance Dashboard

## Current Scope
Append-only audit logging subsystem for OCP with SHA-256 hash chain, searchable compliance dashboard (audit-log.php), CSV/JSON export, automated retention purge, and PostgreSQL trigger-enforced immutability. Status: Specified (Ready for Implementation).

## Relevant Decisions
- **PostgreSQL trigger-enforced immutability**: BEFORE UPDATE OR DELETE trigger blocks application-level mutation; retention purge bypasses via tsisip_retention role.
- **Explicit logAuditEvent() calls per action**: No central PHP interceptor or auto-prepend file — simpler, debuggable, no magic.
- **Hash chain for tamper evidence**: prev_hash -> hash computed per row; offline validator verifyAuditLogIntegrity() provided.
- **Cron inside OCP container**: Daily purge at 03:17 aligns with existing architecture; /var/log/tsisip owned by www-data.

## Active Architecture Constraints
- PostgreSQL-only; no MySQL/MariaDB.
- Docker-first; OCP container updated with cron and log directory.
- opensips DB role has INSERT/SELECT only on ocp_audit_log; tsisip_retention role bypasses trigger for purge.
- No credential material (passwords, HA1 hashes) in details JSONB.
- Parameterized PDO queries for all filters; no raw SQL concatenation.
- Default retention: 90 days via OCP_AUDIT_RETENTION_DAYS.

## Accepted Deviations
- Real-time SIEM integration and SIP-level traffic audit (already covered by auth_audit_log) are out of scope.
- Digital signatures and blockchain anchoring are out of scope.

## Relevant Security Constraints
- Audit entries are append-only at application level.
- Role gates: audit-log.php and audit-export.php require devops role (Admin + DevOps).
- Export stream output to avoid memory exhaustion; capped at 10,000 rows if unbuffered queries are problematic.
- Audit logging is resilient: DB failures during logging are caught and written to error_log() without disrupting the user operation.

## Related Historical Lessons
- Explicit per-action audit calls are simpler and more debuggable than central interceptors.
- Proxy-aware IP resolution (HTTP_X_FORWARDED_FOR then REMOTE_ADDR) is required behind Nginx.
- Input truncation to column max lengths prevents insertion failures on long user agents or details.

## Conflict Warnings
- Depends on Feature 010 (Navigation System Links) for sidebar and dashboard integration.
- Depends on Feature 011 (Forced Password Change) for PASSWORD_CHANGE event instrumentation.
- Instrumentation of subscribers.php and dispatcher.php must not conflict with existing POST handling logic.

## Retrieval Notes
- Search terms: audit log, compliance dashboard, hash chain, immutability trigger, retention purge, audit-export, ocp_audit_log.
- Related features: 010 (navigation), 011 (password change events), 002 (theme/branding).
