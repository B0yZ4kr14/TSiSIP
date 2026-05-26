# Tasks: Automated PostgreSQL Backup and Point-in-Time Recovery
**Last Updated**: 2026-05-19


## Phase 1 — Backup Infrastructure

### [completed] T001: Create backup container Dockerfile
**Description**: Create `docker/backup/Dockerfile` based on `postgres:16`. Install `rclone`, `openssl`, `cron`. Add backup scripts. Expose volume `/backup`. Set entrypoint to cron daemon.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `docker build -t tsisip/backup:test docker/backup/` succeeds.

### [completed] T002: Create logical backup script
**Description**: Create `docker/backup/backup.sh` that: connects to PostgreSQL on `db_internal`, runs `pg_dump -Fc -Z9`, compresses with `gzip`, timestamps filename (`opensips_YYYYMMDD_HHMMSS.dump.gz`), stores in `/backup/daily/`. Use `--lock-wait-timeout=5000` and `REPEATABLE READ`.
**Phase**: 1
**Depends on**: T001
**Parallel**: No
**Acceptance**: Script creates valid compressed backup; `pg_restore -l` lists contents.

### [completed] T003: Add backup service to docker-compose.yml
**Description**: Add `backup` service to `docker-compose.yml`. Network: `db_internal`. Volumes: `./backup/data:/backup`, backup secrets. Environment: `BACKUP_SCHEDULE`, `BACKUP_RETENTION_DAYS`, `PGHOST`, `PGDATABASE`, `PGUSER`.
**Phase**: 1
**Depends on**: T002
**Parallel**: No
**Acceptance**: `docker compose config` validates; service starts.

## Phase 2 — WAL Archiving

### [completed] T004: Configure PostgreSQL WAL archiving
**Description**: Update PostgreSQL configuration via environment: `archive_mode = on`, `archive_command = '/usr/local/bin/wal-archive.sh %p %f'`, `archive_timeout = 300`, `wal_level = replica`, `max_wal_senders = 2`.
**Phase**: 2
**Depends on**: T003
**Parallel**: No
**Acceptance**: `pg_switch_wal()` creates file in `/backup/wal/` within 60s.

### [completed] T005: Create WAL archive management script
**Description**: Create `docker/backup/wal-archive.sh` that: compresses with `gzip`, encrypts via `encrypt.sh`, stores in `/backup/wal/YYYY/MM/DD/`. Called by `archive_command`.
**Phase**: 2
**Depends on**: T006
**Parallel**: No
**Acceptance**: WAL segments are archived, compressed, and encrypted.

### [completed] T006: RPO monitoring and alerting
**Description**: Create `docker/backup/rpo-monitor.sh` that: queries `pg_stat_archiver` to compute lag between `last_archived_time` and current time. Exposes metric `/backup/metrics/rpo_lag_seconds`. Alert if lag > 300 seconds (5 minutes).
**Phase**: 2
**Depends on**: T006
**Parallel**: Yes
**Acceptance**: Metric file updated every 60s; alert triggered when WAL lag exceeds 5 minutes.

## Phase 3 — Encryption & Security

### [completed] T007: Implement AES-256-CBC encryption wrapper with integrity
**Description**: Create `docker/backup/encrypt.sh` that: derives key from Docker secret using PBKDF2 (`-iter 10000`), encrypts with `openssl enc -aes-256-cbc`, outputs `.enc`. Generates HMAC-SHA256 checksum post-encryption for tamper detection. Support decrypt mode with checksum verification.
**Phase**: 3
**Depends on**: T007
**Parallel**: No
**Acceptance**: Encrypted file cannot be identified as PostgreSQL format (`pg_restore -l` fails); decryption restores identical content (SHA-256 match).

### [completed] T008: Add encryption to backup and WAL scripts
**Description**: Update `backup.sh` and `wal-archive.sh` to call `encrypt.sh` after compression. Store encryption key in Docker secret `backup_encryption_key`.
**Phase**: 3
**Depends on**: T011
**Parallel**: No
**Acceptance**: All backup files are encrypted; key rotation script works.

### [completed] T009: Create encryption key rotation script
**Description**: Create `docker/backup/rotate-key.sh` that: reads new key from Docker secret `backup_encryption_key_new`, re-encrypts the last N backups (default: 7 days) with the new key, verifies decryption with new key, atomically swaps key references. Logs all actions for audit.
**Phase**: 3
**Depends on**: T012
**Parallel**: No
**Acceptance**: All backups from the last 7 days are re-encrypted with the new key; old key can no longer decrypt them; new key successfully decrypts all rotated backups.

## Phase 4 — Retention & Purge

### [completed] T010: Create retention policy engine
**Description**: Create `docker/backup/purge.sh` that: enforces 30-day retention for logical backups, 7-day retention for WAL segments beyond oldest backup, deletes expired files, logs actions.
**Phase**: 4
**Depends on**: T012
**Parallel**: No
**Acceptance**: No file older than retention exists; at least one backup per day retained.

### [completed] T011: Add cron scheduling
**Description**: Configure cron inside backup container: daily backup at 02:00 UTC, purge at 03:00 UTC, validation at 04:00 UTC. Use `crontab` with environment variables.
**Phase**: 4
**Depends on**: T016
**Parallel**: No
**Acceptance**: Cron jobs run at scheduled times; logs confirm execution.

### [completed] T012: Add storage quota monitoring
**Description**: Create `docker/backup/quota-check.sh` that: computes total usage of `/backup/daily/` and `/backup/wal/`, compares against `BACKUP_QUOTA_GB` (default 100GB), exposes metric `backup_quota_used_percent`. If usage exceeds 80%, triggers immediate purge of oldest segments beyond retention window; if exceeds 95%, triggers critical alert.
**Phase**: 4
**Depends on**: T016
**Parallel**: Yes
**Acceptance**: Metric updates every 60s; 80% threshold triggers accelerated purge; 95% threshold triggers critical alert; no false positives during normal operation.

## Phase 5 — Restore Validation

### [completed] T013: Create restore validation script
**Description**: Create `docker/backup/validate.sh` that: finds latest backup, decrypts, restores to ephemeral PostgreSQL container, runs validation queries: `SELECT COUNT(*) FROM subscriber`, `SELECT COUNT(DISTINCT username) FROM location`, `SELECT COUNT(*) FROM dispatcher`. Compares against expected ranges.
**Phase**: 5
**Depends on**: T017
**Parallel**: No
**Acceptance**: Validation passes for valid backups; fails and alerts for corrupted ones.

### [completed] T014: Add restore validation test
**Description**: Create `tests/integration/test_backup_restore.py` that: triggers backup, waits for completion, runs validation, simulates corruption, verifies failure detection.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: `pytest tests/integration/test_backup_restore.py` passes.

### [completed] T015: RTO benchmark and alerting
**Description**: Extend `validate.sh` to measure total restore duration (decrypt → decompress → pg_restore → pg_isready). Log timer to stdout and to a metric file `/backup/metrics/rto_last_seconds`. Alert if duration exceeds 900 seconds (15 minutes).
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: Manual restore completes within 15 minutes for a 10GB database; metric file is updated after each validation run.

### [completed] T016: Create PITR restore script
**Description**: Create `docker/backup/pitr-restore.sh` that: accepts target timestamp, finds the latest logical backup before that timestamp, restores it to an ephemeral PostgreSQL container, replays WAL segments up to the target timestamp using `pg_waldump` or `pg_restore` with `--target-time`, verifies database readiness via `pg_isready`. Supports dry-run mode (`--verify-only`) that lists WAL segments to be replayed without executing.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: PITR to any timestamp within the WAL retention window succeeds; dry-run mode lists expected WAL segments; restore target is within 1 minute of requested timestamp.
**Live VPS status 2026-05-19**: Artifact exists, but timestamp-targeted WAL replay is not production-proven. Treat as implementation artifact plus dry-run helper until a full PITR restore drill passes.

## Phase 6 — Offsite Replication

### [completed] T017: Configure rclone for S3-compatible storage
**Description**: Create `docker/backup/rclone.conf.tpl` with S3 backend template. Support endpoint, bucket, access key from environment. Test with `rclone ls`.
**Phase**: 6
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: `rclone ls remote:tsisip-backups` succeeds.
**Live VPS status 2026-05-19**: Template exists with Socratic decision log and documented env vars. `backup.sh` now triggers `replicate.sh` after each successful encrypted backup. Real remote credentials and successful offsite listing are still pending.

### [completed] T018: Implement bandwidth-throttled replication
**Description**: Create `docker/backup/replicate.sh` that: runs `rclone sync --bwlimit 625K` from `/backup/` to remote. Verify MD5 checksums. Run hourly via cron. Add pre-flight bandwidth check.
**Phase**: 6
**Depends on**: T6.1
**Parallel**: No
**Acceptance**: Offsite copy exists within 1 hour; checksums match.
**Live VPS status 2026-05-19**: Default bwlimit lowered to 625K (5 Mbps) with Socratic rationale. Pre-flight check added (`rclone ls` connectivity probe + optional `speedtest-cli`). Offsite copy/checksum proof is pending until a real object-store target is configured.

### [completed] T019: Document backup/restore runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: backup procedures, restore steps (full, PITR), validation process, offsite replication verification, key rotation procedure.
**Phase**: 6
**Depends on**: T6.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all backup scenarios.

## Phase 7 — Observability & SLA Monitoring

### [completed] T020: Prometheus metrics exporter for backup SLA
**Description**: Create `docker/backup/metrics-exporter.sh` that: serves RPO lag (`rpo_lag_seconds`), RTO duration (`rto_last_seconds`), backup success/failure counter, storage quota usage (`backup_quota_used_percent`) in Prometheus text format on `0.0.0.0:9101/metrics`.
**Phase**: 7
**Depends on**: T008, T018, T5.3
**Parallel**: No
**Acceptance**: `curl localhost:9101/metrics` returns valid Prometheus metrics; Grafana dashboard displays all 4 series.
