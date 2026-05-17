# Requirements Checklist: Automated PostgreSQL Backup and Point-in-Time Recovery

## Functional Requirements

- [x] FR-001: Scheduled Logical Backups — Daily pg_dump with custom format and gzip compression via backup sidecar.
- [x] FR-002: WAL Archiving for PITR — PostgreSQL archive_mode enabled with archive_command and archive_timeout.
- [x] FR-003: Backup Encryption at Rest — AES-256-CBC + PBKDF2 encryption using Docker secret-derived keys.
- [x] FR-004: Retention Policies — 30-day logical backup retention; 7-day WAL retention beyond oldest backup.
- [x] FR-005: Restore Validation Tests — Daily ephemeral restore with row-count and checksum assertions.
- [x] FR-006: Offsite Backup Replication — S3-compatible replication with bandwidth throttling and checksum verification.

## Success Criteria

- [x] SC-001: Logical backup duration ≤ 30 minutes for 10GB database.
- [x] SC-002: Recovery Point Objective (RPO) ≤ 5 minutes.
- [x] SC-003: Recovery Time Objective (RTO) ≤ 15 minutes for PITR.
- [x] SC-004: Restore validation pass rate 100% daily.
- [x] SC-005: Backup encryption strength meets AES-256-CBC + PBKDF2 + HMAC-SHA256.
- [x] SC-006: Offsite replication lag ≤ 1 hour.

## Risks

- [x] R-001: pg_dump lock contention mitigated by off-peak scheduling and lock-wait-timeout.
- [x] R-002: WAL storage exhaustion mitigated by monitoring and auto-purge.
- [x] R-003: Encryption key loss mitigated by Docker secret storage and key history.
- [x] R-004: Network saturation mitigated by bandwidth throttling.
- [x] R-005: PITR overshoot documented and accepted as end-of-last-transaction behavior.

**Status: PASS**
