# Implementation Plan: Automated PostgreSQL Backup and Point-in-Time Recovery

## Overview

This plan translates the feature specification into an executable implementation roadmap for automated logical backups, WAL archiving, encryption, retention policies, restore validation, and offsite replication for the TSiSIP PostgreSQL database.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2
- Dedicated `backup` service running on `db_internal` network
- Sidecar pattern for WAL archiving

### Backup Tools
- **pg_dump**: Logical backups in custom format (`-Fc`)
- **pg_basebackup**: Physical base backup for PITR
- **WAL-G**: WAL archiving and PITR management (optional, evaluated)
- **openssl**: AES-256-GCM encryption

### Storage Backend
- Local volume: `/backup` on host for immediate recovery
- Offsite: S3-compatible object store (configurable)
- **rclone**: Bandwidth-throttled replication

### Scheduler
- **cron** inside backup container
- Environment-configurable schedule (default: 02:00 UTC)

---

## Implementation Phases

### Phase 1 — Backup Infrastructure
- Backup container Dockerfile with pg_dump, openssl, rclone
- Docker Compose service definition
- Backup scripts: logical backup, compression, encryption
- Volume mounts for backup storage

### Phase 2 — WAL Archiving
- PostgreSQL archive_mode and archive_command configuration
- WAL segment shipping to backup store
- archive_timeout configuration

### Phase 3 — Encryption & Security
- AES-256-GCM encryption with Docker secrets
- Key derivation (PBKDF2)
- Key rotation support

### Phase 4 — Retention & Purge
- Retention policy engine
- Automated purge job
- Storage quota monitoring

### Phase 5 — Restore Validation
- Ephemeral restore container
- Validation queries for critical tables
- Alerting on validation failure

### Phase 6 — Offsite Replication
- rclone configuration for S3-compatible store
- Bandwidth throttling (50 Mbps)
- Checksum verification

---

## File Structure

```
docker/
  backup/
    Dockerfile
    backup.sh                # Main backup script
    restore.sh               # Restore script
    validate.sh              # Validation script
    encrypt.sh               # Encryption/decryption wrapper
    purge.sh                 # Retention purge
    rclone.conf.tpl          # rclone config template
opensips/
  postgresql.conf.tpl        # PostgreSQL config with archive settings
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Build | Backup image builds | `docker compose build backup` |
| Backup | Logical backup completes | `docker compose exec backup backup.sh` |
| WAL | Archive segment created | `ls /backup/wal/` |
| Encrypt | Backup is encrypted | `file /backup/*.dump.gz.enc` |
| Restore | Validation passes | `docker compose exec backup validate.sh` |
| Offsite | rclone sync completes | `rclone ls remote:tsisip-backups` |
