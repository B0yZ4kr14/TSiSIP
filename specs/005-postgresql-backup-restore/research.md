# Research: Automated PostgreSQL Backup and Point-in-Time Recovery

## Decision: pg_dump vs pg_basebackup

**Decision**: Use `pg_dump -Fc` for logical backups, `pg_basebackup` for physical base backup if PITR needed.

**Rationale**:
- `pg_dump` is portable, version-agnostic, and human-inspectable
- `pg_basebackup` is required for WAL-based PITR
- Both can coexist: logical for daily, physical for weekly base
- Feature spec explicitly requests logical backups

**Alternatives considered**:
- `pg_dumpall`: unnecessary, only one database
- Continuous archiving only: no standalone recovery point
- Third-party tools (Barman, pgBackRest): overkill for single-node setup

## Decision: AES-256-GCM vs OpenSSL default enc

**Decision**: Use `openssl enc -aes-256-cbc` with PBKDF2 (default in OpenSSL 1.1+).

**Rationale**:
- OpenSSL 1.1+ uses PBKDF2 by default with 10000 iterations
- AES-256-CBC is widely supported and well-audited
- GCM requires newer OpenSSL and has limited compatibility
- Backup files are not streamed, so AEAD is less critical

**Alternatives considered**:
- AES-256-GCM: better integrity but compatibility issues
- age (Filippo Valsorda): modern but not in standard repos
- GnuPG: complex key management for automated backups

## Decision: rclone vs s3cmd

**Decision**: Use `rclone` for offsite replication.

**Rationale**:
- rclone supports 70+ storage backends
- Built-in bandwidth limiting (`--bwlimit`)
- Checksum verification and resume support
- Single binary, easy to install

**Alternatives considered**:
- s3cmd: S3-only, less flexible
- awscli: AWS-only, heavy dependency
- Restic: deduplication but more complex

## Decision: Backup Scheduling

**Decision**: Use cron inside backup container.

**Rationale**:
- Simple, proven, no external dependencies
- Container-native: schedule travels with deployment
- Easy to customize via environment variables
- Docker restart policy handles missed jobs

**Alternatives considered**:
- Host cron: breaks container encapsulation
- Kubernetes CronJob: requires K8s
- Systemd timers: host-level, not portable

## Decision: WAL Retention

**Decision**: WAL segments retained for 7 days beyond oldest logical backup.

**Rationale**:
- Allows PITR to any point within the backup window
- 7 days is safe margin for recovery
- Auto-purge prevents storage exhaustion
- Aligns with PostgreSQL best practices

## Falsification Hypotheses

1. **Hypothesis**: pg_dump locks subscriber table >5s during peak load.
   **Test**: Run backup during simulated peak registration load.
   **Mitigation**: If true, use `--snapshot` or reduce lock timeout.

2. **Hypothesis**: WAL archive grows >1GB/day.
   **Test**: Monitor WAL generation for 7 days.
   **Mitigation**: If true, increase archive_timeout or add compression.

3. **Hypothesis**: Offsite replication saturates network.
   **Test**: Monitor bandwidth during replication.
   **Mitigation**: If true, reduce bwlimit or schedule during off-peak.
