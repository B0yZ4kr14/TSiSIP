# Feature 005 Memory: Automated PostgreSQL Backup and Point-in-Time Recovery

## Current Scope
Automated logical backups (pg_dump), WAL archiving for PITR, AES-256-CBC encryption at rest, retention policies, daily restore validation into ephemeral containers, and offsite replication via rclone to MinIO. Status: Implemented.

## Relevant Decisions
- **Logical backups over physical**: pg_dump -Fc is sufficient for the current 10GB baseline; physical replication deferred.
- **MinIO self-hosted (TSiHomeLab)** over cloud object storage: eliminates recurring costs, keeps data within the Tailscale private network (100.64.0.0/10).
- **Cron inside backup container**: Aligns with Docker-first delivery; no host-level cron dependency.
- **Process-level I/O throttling**: ionice -c2 -n7 and nice -n 10 reduce backup impact on PostgreSQL.

## Active Architecture Constraints
- PostgreSQL is the sole persistence layer; db_postgres is the only OpenSIPS DB module.
- WAL archives stored outside the PostgreSQL container to survive container deletion.
- All identifiers and service names use lowercase snake_case.
- LGPD-aligned: 30-day retention baseline, encryption, audit logging.

## Accepted Deviations
- Full timestamp-targeted PITR replay is not yet production-proven (live VPS status 2026-05-19: artifact exists but WAL replay drill pending).
- Offsite replication is pending real rclone/MinIO credentials; local encrypted backup path is validated.

## Relevant Security Constraints
- AES-256-CBC + PBKDF2 (-iter 10000) + HMAC-SHA256 for all backup artifacts.
- Encryption key derived from Docker secret backup_encryption_key.
- Key rotation supported by re-encrypting last N backups.
- Key loss is critical — store in Docker secret with host-level backup and maintain key history.

## Related Historical Lessons
- pg_dump does not accept transaction-isolation flags; enforce REPEATABLE READ via PGOPTIONS environment variable.
- --lock-wait-timeout=5000 prevents long-running locks during peak registration volume.
- First-day validation returns skipped (no alert) when no prior backup exists.
- Same-target restores serialized via lock file; different-target restores allowed concurrently.

## Conflict Warnings
- Backup metrics (rpo_lag_seconds, rto_last_seconds) are scraped by Prometheus and feed into Feature 003 alerting.

## Retrieval Notes
- Search terms: backup, pg_dump, WAL archive, PITR, encryption, rclone, MinIO, retention, restore validation.
- Related features: 003 (Prometheus alerting on backup metrics), 004 (backup container healthcheck).
