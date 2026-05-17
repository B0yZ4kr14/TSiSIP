# Feature Specification: Automated PostgreSQL Backup and Point-in-Time Recovery

## Overview

| Field | Value |
|-------|-------|
| **Feature** | Automated PostgreSQL Backup and Point-in-Time Recovery |
| **Short name** | postgresql-backup-restore |
| **Created** | 2026-05-16 |
| **Status** | Draft |
| **Context** | TSiSIP uses PostgreSQL as the sole database for subscriber tables, location records, and dispatcher state. Data durability and fast recovery are mandatory for production SIP service continuity. |
| **Objective** | Provide automated logical backups, WAL archiving for PITR, encrypted at-rest storage, retention policies, restore validation, and offsite replication to protect against data loss and enable granular recovery. |

## User Scenarios & Testing

### Scenario 1: Nightly Logical Backup Completes and Encrypts
**Given** the backup scheduler is configured,
**When** the cron job triggers at 02:00 UTC,
**Then** a `pg_dump` logical backup is created, compressed with `gzip`, encrypted with AES-256-CBC+PBKDF2, and uploaded to the backup storage backend.

### Scenario 2: Point-in-Time Recovery to 15 Minutes Before Accidental Deletion
**Given** WAL archiving is active and continuous archiving streams to the backup store,
**When** an operator requests recovery to `2026-05-16T13:45:00Z`,
**Then** PostgreSQL replays WAL segments up to that timestamp and restores the database to the requested point in time.
> **PITR Scope**: Logical backup (`pg_dump`) + WAL archive replay. Physical base backup (`pg_basebackup`) is not required for the current scope.

### Scenario 3: Restore Validation Test Passes in Staging
**Given** a backup artifact exists from the previous night,
**When** the automated validation job restores it into an ephemeral PostgreSQL container,
**Then** all critical tables (`subscriber`, `location`, `dispatcher`) pass row-count and checksum assertions.

### Edge Case 1: Backup Job Runs During Peak Load
The backup must use `pg_dump` with `--lock-wait-timeout` and `REPEATABLE READ` to avoid long-running locks on the `subscriber` table during high registration volume.

### Edge Case 2: WAL Archive Storage Reaches Capacity
When WAL storage exceeds 80% of the retention quota, the oldest archived WAL segments beyond the retention window must be purged automatically before new segments are rejected.

### Edge Case 3: Corrupted Backup Artifact Detected
If the encrypted backup fails decryption or the decompressed SQL contains syntax errors, the validation test must fail, alert the operator, and reference the previous valid backup.

## Functional Requirements

### FR-001: Scheduled Logical Backups
- A dedicated backup container or sidecar runs `pg_dump` against the `db_internal` network target.
- Schedule: daily at 02:00 UTC; configurable via environment variable.
- Output: custom-format (`-Fc`) with `gzip` compression.
- **Acceptance Criteria**: Backup completes within 30 minutes for a 10GB database; zero lock-timeout errors in PostgreSQL logs.

### FR-002: WAL Archiving for PITR
- PostgreSQL `archive_mode = on` and `archive_command` copies completed WAL segments to the backup store.
- `archive_timeout = 300` to ensure WAL rotation even during low activity.
- **Acceptance Criteria**: `pg_switch_wal()` results in a new archived segment visible in the backup store within 60 seconds.

### FR-003: Backup Encryption at Rest
- All logical backups and WAL segments are encrypted using **AES-256-CBC with PBKDF2** (OpenSSL `-aes-256-cbc -pbkdf2 -iter 10000`) using a key derived from a Docker secret.
- A separate HMAC-SHA256 integrity check is applied post-encryption to detect tampering.
- Key rotation is supported by re-encrypting the last N backups with the new key.
- **Acceptance Criteria**: Encrypted file header does not match plaintext PostgreSQL custom format (`pg_restore -l` fails on encrypted file); decryption with the correct key restores identical byte-for-byte content (verified by SHA-256 checksum).

### FR-004: Retention Policies
- Logical backups retained for 30 days; WAL segments retained for 7 days beyond the oldest logical backup.
- Automated purge job runs daily at 03:00 UTC.
- **Acceptance Criteria**: No backup or WAL segment older than the retention window exists in the store; at least one backup per day is retained.
- **Storage Quota**: When backup volume usage exceeds 80% of allocated quota (`BACKUP_QUOTA_GB`, default 100GB), oldest segments beyond retention are purged immediately.

### FR-005: Restore Validation Tests
- An ephemeral PostgreSQL container (`docker run --rm postgres:16`) is spawned from the latest logical backup daily.
- Validation queries verify:
  - `subscriber`: row count > 0
  - `location`: COUNT(DISTINCT username) = COUNT(*) (AOR uniqueness)
  - `dispatcher`: COUNT(*) > 0 and MAX(setid) ≥ MIN(setid)
- **Acceptance Criteria**: All validation queries pass within 10 minutes; failures trigger a critical alert.
- **RTO Benchmark**: Restore from latest logical backup to SIP-ready state must complete within 15 minutes (measured by `validate.sh` timer).

### FR-006: Offsite Backup Replication
- Encrypted backups and WAL segments are replicated to an offsite object store (e.g., S3-compatible) using `rclone` or `s3cmd`.
- Replication bandwidth is throttled to 50 Mbps to avoid saturating `sip_edge` or `sip_internal` networks.
- **Acceptance Criteria**: Offsite copy exists within 1 hour of local backup completion; MD5 checksums match.

## Success Criteria

| ID | Criterion | Target | Measurement Method |
|----|-----------|--------|-------------------|
| SC-001 | Logical backup duration | ≤ 30 minutes for 10GB | Timer on backup job logs |
| SC-002 | RPO (Recovery Point Objective) | ≤ 5 minutes | `pg_stat_archiver.last_archived_time` lag vs. current time |
| SC-003 | RTO (Recovery Time Objective) | ≤ 15 minutes for PITR | `validate.sh` timer from restore start to `pg_isready` |
| SC-004 | Restore validation pass rate | 100% daily | Automated test report |
| SC-005 | Backup encryption strength | AES-256-CBC + PBKDF2 + HMAC-SHA256 | `openssl` cipher verification + checksum match |
| SC-006 | Offsite replication lag | ≤ 1 hour | Object store listing timestamp comparison |

## Key Entities

| Entity | Description | Attributes |
|--------|-------------|------------|
| BackupArtifact | A single logical backup file | id, created_at, size_bytes, checksum, encryption_key_id, retention_until |
| WALSegment | A single Write-Ahead Log segment | name, start_lsn, end_lsn, archived_at, size_bytes |
| RestoreJob | An execution of a restore or validation test | trigger_type, target_timestamp, status, duration_ms, validation_results |

## Scope

### In Scope
- `pg_dump` logical backup automation inside the Docker Compose stack.
- PostgreSQL WAL archiving configuration (`archive_mode`, `archive_command`).
- Encryption at rest for all backup artifacts.
- Retention policy engine and automated purging.
- Daily restore validation into ephemeral containers.
- Offsite replication to S3-compatible storage.

### Out of Scope
- Continuous physical replication (streaming replication) between primary and standby.
- Backup of RTPengine kernel state or Asterisk voicemail spools.
- Host-level filesystem snapshots (LVM/ZFS).
- Cross-region multi-master PostgreSQL.

## Dependencies

| Dependency | Description | Impact if Missing |
|------------|-------------|-------------------|
| PostgreSQL 15+ | WAL archiving, PITR, `pg_dump` custom format | Cannot implement backup or PITR |
| Object Store / Volume | Local and offsite storage for artifacts | No durability or offsite protection |
| Docker Compose | Orchestration of backup sidecar and ephemeral validation containers | Cannot run isolated validation jobs |
| Encryption Key Management | Docker secrets or external KMS for AES keys | Cannot meet at-rest encryption requirement |

## Assumptions

- The PostgreSQL data volume is mounted at a known path (e.g., `/var/lib/postgresql/data`) inside the container.
- The backup sidecar has network access to `db_internal` and can authenticate with PostgreSQL using a superuser role.
- Sufficient disk space is available locally for at least 2x the database size (active data + backups).
- Offsite object store credentials are available as Docker secrets.

## Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|------------|--------|------------|
| R-001 | `pg_dump` locks cause registration timeouts during peak hours | Medium | High | Schedule during low traffic; use `--lock-wait-timeout`; read replica if introduced later |
| R-002 | WAL archive storage fills up and halts PostgreSQL | Medium | High | Monitor disk usage; auto-purge beyond retention; set `archive_timeout` |
| R-003 | Encryption key loss renders all backups unreadable | Low | Critical | Store key in Docker secret with host-level backup; maintain key history |
| R-004 | Offsite replication saturates network and impacts SIP signaling | Medium | Medium | Throttle bandwidth; schedule replication outside peak hours |
| R-005 | PITR restore overshoots target due to overlapping transactions | Low | Medium | Document that PITR restores to the end of the last committed transaction before the target time |

## Notes

- All PostgreSQL identifiers and Docker service names must use lowercase snake_case (e.g., `db_internal`, `backup_sidecar`).
> **Constitution Reference**: See `.specify/memory/constitution.md` §2 — PostgreSQL is the sole persistence layer; `db_postgres` is the only OpenSIPS DB module.
- WAL archives must be stored outside the PostgreSQL container to survive container deletion.
- Retention policy must respect compliance requirements; the default 30 days is a baseline and may be increased per deployment.
- Validation test containers must be destroyed immediately after test completion to free resources.
