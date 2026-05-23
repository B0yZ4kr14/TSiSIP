# Feature 005 Memory Synthesis: Automated PostgreSQL Backup and Point-in-Time Recovery

## Current Scope
Automated pg_dump backups, WAL archiving, AES-256-CBC encryption, retention, daily validation, and offsite rclone replication. Status: Implemented.

## Relevant Decisions
- Logical backups sufficient for 10GB baseline; physical replication deferred.
- MinIO self-hosted over cloud (cost, privacy).
- Cron inside backup container.
- I/O throttling via ionice/nice.

## Active Architecture Constraints
- PostgreSQL-only persistence.
- WAL archives outside container.
- LGPD-aligned retention and encryption.

## Accepted Deviations
- Full PITR replay not yet production-proven.
- Offsite replication pending real credentials.

## Relevant Security Constraints
- AES-256-CBC + PBKDF2 + HMAC-SHA256.
- Key via Docker secret; rotation supported.

## Related Historical Lessons
- REPEATABLE READ via PGOPTIONS, not pg_dump flag.
- --lock-wait-timeout prevents peak-hour locks.
- First-day validation skips gracefully.

## Conflict Warnings
- Backup metrics feed Feature 003 alerting.

## Retrieval Notes
- Keywords: backup, pg_dump, WAL, PITR, encryption, rclone, retention.
- Related: 003, 004.
