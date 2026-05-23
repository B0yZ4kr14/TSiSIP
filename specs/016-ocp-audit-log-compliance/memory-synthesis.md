# Feature 016 Memory Synthesis: OCP Audit Log & Compliance Dashboard

## Current Scope
Append-only audit log with SHA-256 hash chain, compliance dashboard, CSV/JSON export, retention purge, and PostgreSQL immutability trigger.

## Relevant Decisions
- Trigger-enforced immutability; retention bypass via tsisip_retention role.
- Explicit logAuditEvent() per action (no central interceptor).
- Hash chain for tamper evidence.
- Cron inside OCP container.

## Active Architecture Constraints
- PostgreSQL-only; Docker-first.
- opensips role: INSERT/SELECT only.
- No credentials in details JSONB.
- PDO parameterized queries only.
- 90-day default retention.

## Accepted Deviations
- SIEM integration out of scope.
- Digital signatures/blockchain out of scope.

## Relevant Security Constraints
- Append-only application-level.
- devops role gate on dashboard/export.
- Export streaming prevents memory exhaustion.
- Logging failures caught silently.

## Related Historical Lessons
- Per-action calls simpler than interceptors.
- Proxy-aware IP resolution required behind Nginx.
- Input truncation prevents column overflow.

## Conflict Warnings
- Depends on Feature 010 navigation and Feature 011 password change instrumentation.

## Retrieval Notes
- Keywords: audit log, compliance dashboard, hash chain, immutability, retention purge.
- Related: 010, 011, 002.
