# Blueprint — Automated PostgreSQL Backup and Point-in-Time Recovery

## Overview

Provide automated logical backups, WAL archiving for PITR, encrypted at-rest storage, retention policies, restore validation, and offsite replication to protect against data loss and enable granular recovery for the TSiSIP PostgreSQL database.

## Requirements

- **FR-005-001**: Scheduled Logical Backups — daily at 02:00 UTC; `pg_dump -Fc -Z9`; completes within 30 minutes for 10GB database.
- **FR-005-002**: WAL Archiving for PITR — `archive_mode = on`, `archive_command` copies WAL segments, `archive_timeout = 300`.
- **FR-005-003**: Backup Encryption at Rest — AES-256-CBC + PBKDF2 (`-iter 10000`) via OpenSSL; HMAC-SHA256 integrity check.
- **FR-005-004**: Retention Policies — logical backups 30 days; WAL segments 7 days beyond oldest backup; automated purge at 03:00 UTC; storage quota monitoring.
- **FR-005-005**: Restore Validation Tests — ephemeral PostgreSQL container spawned daily; row-count and checksum assertions; RTO ≤15 minutes.
- **FR-005-006**: Offsite Backup Replication — `rclone` to MinIO self-hosted on TSiHomeLab via Tailscale; bandwidth-throttled to 50 Mbps; MD5 checksums match.

## Architecture

- **Container Platform**: Docker Engine with Docker Compose V2; dedicated `backup` service on `db_internal`.
- **Backup Tools**: `pg_dump` (custom format `-Fc`); `openssl` (AES-256-CBC + PBKDF2).
- **Storage Backend**: Local volume `/backup`; offsite MinIO via Tailscale (`100.64.0.0/10`).
- **Scheduler**: `cron` inside backup container; environment-configurable schedule.
- **RPO/RTO Targets**: RPO ≤5 minutes; RTO ≤15 minutes.

## Implementation Plan

### Phase 1 — Backup Infrastructure
- Backup container Dockerfile with `pg_dump`, `openssl`, `rclone`.
- Docker Compose service definition.
- Backup scripts: logical backup, compression, encryption.
- Volume mounts for backup storage.

### Phase 2 — WAL Archiving
- PostgreSQL `archive_mode` and `archive_command` configuration.
- WAL segment shipping to backup store.
- `archive_timeout` configuration.

### Phase 3 — Encryption & Security
- AES-256-CBC + PBKDF2 encryption with Docker secrets.
- HMAC-SHA256 integrity verification.
- Key rotation support.

### Phase 4 — Retention & Purge
- Retention policy engine.
- Automated purge job.
- Storage quota monitoring.

### Phase 5 — Restore Validation, PITR & RTO Benchmark
- Ephemeral restore container.
- Validation queries for critical tables.
- PITR restore script with dry-run mode.
- RTO timer and alerting via Prometheus Alertmanager.

### Phase 6 — Offsite Replication
- `rclone` configuration for MinIO endpoint.
- Bandwidth throttling (50 Mbps).
- Checksum verification.

### Phase 7 — Observability & SLA Monitoring
- RPO monitor; RTO benchmark metrics.
- Backup success/failure metrics exposed for Prometheus.
- Grafana dashboard for backup status, RPO/RTO trends, storage quota.

## Tasks

**Phase 1 — Backup Infrastructure**
- T1.1: Create backup container Dockerfile
- T1.2: Create logical backup script
- T1.3: Add backup service to `docker-compose.yml`

**Phase 2 — WAL Archiving**
- T2.1: Configure PostgreSQL WAL archiving
- T2.2: Create WAL archive management script
- T2.3: RPO monitoring and alerting

**Phase 3 — Encryption & Security**
- T3.1: Implement AES-256-CBC encryption wrapper with integrity
- T3.2: Add encryption to backup and WAL scripts
- T3.3: Create encryption key rotation script

**Phase 4 — Retention & Purge**
- T4.1: Create retention policy engine
- T4.2: Add cron scheduling
- T4.3: Add storage quota monitoring

**Phase 5 — Restore Validation**
- T5.1: Create restore validation script
- T5.2: Add restore validation test
- T5.3: RTO benchmark and alerting
- T5.4: Create PITR restore script

**Phase 6 — Offsite Replication**
- T6.1: Configure `rclone` for S3-compatible storage
- T6.2: Implement bandwidth-throttled replication
- T6.3: Document backup/restore runbook

**Phase 7 — Observability & SLA Monitoring**
- T7.1: Prometheus metrics exporter for backup SLA

## Validation

- `docker compose build backup` succeeds.
- Backup completes; `pg_restore -l` lists contents.
- `pg_switch_wal()` creates file in `/backup/wal/` within 60s.
- Encrypted file cannot be identified as PostgreSQL format; decryption restores identical content.
- No file older than retention exists; at least one backup per day retained.
- Validation passes for valid backups; fails and alerts for corrupted ones.
- PITR to any timestamp within WAL retention window succeeds.
- Offsite copy exists within 1 hour; checksums match.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| `pg_dump` locks cause registration timeouts during peak | Schedule during low traffic; use `--lock-wait-timeout`; I/O throttling |
| WAL archive storage fills up and halts PostgreSQL | Monitor disk; auto-purge beyond retention; set `archive_timeout` |
| Encryption key loss renders all backups unreadable | Store key in Docker secret with host-level backup; maintain key history |
| Offsite replication saturates network | Throttle bandwidth; schedule outside peak hours |
| PITR restore overshoots target | Document restore to end of last committed transaction before target time |

**Dependencies**: PostgreSQL 15+; MinIO (TSiHomeLab); Docker Compose; encryption key management via Docker secrets.
