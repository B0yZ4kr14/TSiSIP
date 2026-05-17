# Tasks: Automated PostgreSQL Backup and Point-in-Time Recovery

## Phase 1 — Backup Infrastructure

### [ ] T1.1: Create backup container Dockerfile
**Description**: Create `docker/backup/Dockerfile` based on `postgres:16`. Install `rclone`, `openssl`, `cron`. Add backup scripts. Expose volume `/backup`. Set entrypoint to cron daemon.
**Phase**: 1
**Depends on**: —
**Parallel**: No
**Acceptance**: `docker build -t tsisip/backup:test docker/backup/` succeeds.

### [ ] T1.2: Create logical backup script
**Description**: Create `docker/backup/backup.sh` that: connects to PostgreSQL on `db_internal`, runs `pg_dump -Fc -Z9`, compresses with `gzip`, timestamps filename (`opensips_YYYYMMDD_HHMMSS.dump.gz`), stores in `/backup/daily/`. Use `--lock-wait-timeout=5000` and `REPEATABLE READ`.
**Phase**: 1
**Depends on**: T1.1
**Parallel**: No
**Acceptance**: Script creates valid compressed backup; `pg_restore -l` lists contents.

### [ ] T1.3: Add backup service to docker-compose.yml
**Description**: Add `backup` service to `docker-compose.yml`. Network: `db_internal`. Volumes: `./backup/data:/backup`, backup secrets. Environment: `BACKUP_SCHEDULE`, `BACKUP_RETENTION_DAYS`, `PGHOST`, `PGDATABASE`, `PGUSER`.
**Phase**: 1
**Depends on**: T1.2
**Parallel**: No
**Acceptance**: `docker compose config` validates; service starts.

## Phase 2 — WAL Archiving

### [ ] T2.1: Configure PostgreSQL WAL archiving
**Description**: Update PostgreSQL configuration via `db/init/03-archive-config.sql` or environment: `archive_mode = on`, `archive_command = 'cp %p /backup/wal/%f'`, `archive_timeout = 300`, `wal_level = replica`, `max_wal_senders = 2`.
**Phase**: 2
**Depends on**: T1.3
**Parallel**: No
**Acceptance**: `pg_switch_wal()` creates file in `/backup/wal/` within 60s.

### [ ] T2.2: Create WAL archive management script
**Description**: Create `docker/backup/wal-archive.sh` that: verifies WAL segment integrity, compresses with `gzip`, encrypts, moves to `/backup/wal/YYYY/MM/DD/`. Called by `archive_command`.
**Phase**: 2
**Depends on**: T2.1
**Parallel**: No
**Acceptance**: WAL segments are archived, compressed, and encrypted.

## Phase 3 — Encryption & Security

### [ ] T3.1: Implement AES-256-GCM encryption wrapper
**Description**: Create `docker/backup/encrypt.sh` that: derives key from Docker secret using PBKDF2, encrypts input file, outputs `.enc`. Support decrypt mode. Use `openssl enc -aes-256-gcm`.
**Phase**: 3
**Depends on**: T2.2
**Parallel**: No
**Acceptance**: Encrypted file cannot be identified as PostgreSQL format; decryption restores original.

### [ ] T3.2: Add encryption to backup and WAL scripts
**Description**: Update `backup.sh` and `wal-archive.sh` to call `encrypt.sh` after compression. Store encryption key in Docker secret `backup_encryption_key`.
**Phase**: 3
**Depends on**: T3.1
**Parallel**: No
**Acceptance**: All backup files are encrypted; key rotation script works.

## Phase 4 — Retention & Purge

### [ ] T4.1: Create retention policy engine
**Description**: Create `docker/backup/purge.sh` that: enforces 30-day retention for logical backups, 7-day retention for WAL segments beyond oldest backup, deletes expired files, logs actions.
**Phase**: 4
**Depends on**: T3.2
**Parallel**: No
**Acceptance**: No file older than retention exists; at least one backup per day retained.

### [ ] T4.2: Add cron scheduling
**Description**: Configure cron inside backup container: daily backup at 02:00 UTC, purge at 03:00 UTC, validation at 04:00 UTC. Use `crontab` with environment variables.
**Phase**: 4
**Depends on**: T4.1
**Parallel**: No
**Acceptance**: Cron jobs run at scheduled times; logs confirm execution.

## Phase 5 — Restore Validation

### [ ] T5.1: Create restore validation script
**Description**: Create `docker/backup/validate.sh` that: finds latest backup, decrypts, restores to ephemeral PostgreSQL container, runs validation queries: `SELECT COUNT(*) FROM subscriber`, `SELECT COUNT(DISTINCT username) FROM location`, `SELECT COUNT(*) FROM dispatcher`. Compares against expected ranges.
**Phase**: 5
**Depends on**: T4.2
**Parallel**: No
**Acceptance**: Validation passes for valid backups; fails and alerts for corrupted ones.

### [ ] T5.2: Add restore validation test
**Description**: Create `tests/integration/test_backup_restore.py` that: triggers backup, waits for completion, runs validation, simulates corruption, verifies failure detection.
**Phase**: 5
**Depends on**: T5.1
**Parallel**: No
**Acceptance**: `pytest tests/integration/test_backup_restore.py` passes.

## Phase 6 — Offsite Replication

### [ ] T6.1: Configure rclone for S3-compatible storage
**Description**: Create `docker/backup/rclone.conf.tpl` with S3 backend template. Support endpoint, bucket, access key from environment. Test with `rclone ls`.
**Phase**: 6
**Depends on**: T5.2
**Parallel**: No
**Acceptance**: `rclone ls remote:tsisip-backups` succeeds.

### [ ] T6.2: Implement bandwidth-throttled replication
**Description**: Create `docker/backup/replicate.sh` that: runs `rclone sync --bwlimit 50M` from `/backup/` to remote. Verify MD5 checksums. Run hourly via cron.
**Phase**: 6
**Depends on**: T6.1
**Parallel**: No
**Acceptance**: Offsite copy exists within 1 hour; checksums match.

### [ ] T6.3: Document backup/restore runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with: backup procedures, restore steps (full, PITR), validation process, offsite replication verification, key rotation procedure.
**Phase**: 6
**Depends on**: T6.2
**Parallel**: No
**Acceptance**: Runbook contains actionable procedures for all backup scenarios.
