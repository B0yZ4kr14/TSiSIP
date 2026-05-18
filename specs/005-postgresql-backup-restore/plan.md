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
- **openssl**: AES-256-CBC + PBKDF2 encryption (HMAC-SHA256 for integrity)

### Storage Backend
- Local volume: `/backup` on host for immediate recovery
- Offsite: MinIO self-hosted no TSiHomeLab, acessível via Tailscale (100.64.0.0/10)
- **rclone**: Bandwidth-throttled replication (50 Mbps)

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
- AES-256-CBC + PBKDF2 encryption with Docker secrets
- HMAC-SHA256 integrity verification
- Key rotation support

### Phase 4 — Retention & Purge
- Retention policy engine
- Automated purge job
- Storage quota monitoring

### Phase 5 — Restore Validation & RTO Benchmark
- Ephemeral restore container
- Validation queries for critical tables
- RTO timer: restore-to-ready must complete within 15 minutes
- Alerting via Prometheus Alertmanager (webhook) em caso de falha de validação ou RTO breach
- Edge case: no primeiro dia sem backup anterior, validação retorna `skipped` com log informativo; nenhum alerta é disparado

### Phase 6 — Offsite Replication
### Phase 7 — Observability & SLA Monitoring
- RPO monitor: query `pg_stat_archiver` lag every 60s; alert if > 5 minutes
- RTO benchmark: `validate.sh` logs restore duration; alert if > 15 minutes
- Backup success/failure metrics expostas em `/backup/metrics` para Prometheus scraping
- Alertas roteados pelo Alertmanager (webhook); canal final configurável (Slack/email/PagerDuty)
- Grafana dashboard: backup status, RPO/RTO trends, storage quota usage
- rclone configuration para endpoint MinIO no TSiHomeLab
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
docker/postgres/
  postgresql.conf.tpl        # PostgreSQL config with archive settings
```

---

## Validation Gates

| Gate | Check | Command |
|---|---|---|
| Build | Backup image builds | `docker compose build backup` |
| Backup | Logical backup completes | `docker compose exec backup backup.sh` |
| WAL | Archive segment created | `ls /backup/wal/` |
| Encrypt | Backup is encrypted | `openssl enc -aes-256-cbc -d -pbkdf2 ...` |
| Restore | Validation passes | `docker compose exec backup validate.sh` |
| Offsite | rclone sync completes | `rclone ls remote:tsisip-backups` |
