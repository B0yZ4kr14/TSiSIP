# Feature 032 Tasks: Automated Backup Verification & Disaster Recovery Testing

**Last Updated**: 2026-05-27

---

## Phase 1: Checksum & Backup Enhancement

### T001: Add SHA-256 checksum generation to backup script
**Description**: Update `scripts/backup-db.sh` to generate a `.sha256` file for each backup immediately after creation. Store it alongside the backup file.
**Files affected**: `scripts/backup-db.sh`
**Depends on**: —
**Status**: [x]

### T002: Verify checksum on backup completion
**Description**: After generating the checksum, immediately verify it (`sha256sum -c`). Log the result.
**Files affected**: `scripts/backup-db.sh`
**Depends on**: T001
**Status**: [x]

### T003: Create backup metadata JSON
**Description**: Generate a `.meta.json` file for each backup containing timestamp, size, checksum, retention class (daily/weekly/monthly), and verification status.
**Files affected**: `scripts/backup-db.sh`
**Depends on**: T001
**Status**: [x]

---

## Phase 2: Restore Verification Script

### T004: Create verify-backup.sh script
**Description**: Create `scripts/verify-backup.sh` that takes a backup file path, spins up a temporary PostgreSQL container, restores the dump, runs validation queries (table counts, schema version, critical rows), and reports PASS/FAIL.
**Files affected**: `scripts/verify-backup.sh`
**Depends on**: T001
**Status**: [x]

### T005: Handle encrypted backup verification
**Description**: If the backup file ends with `.enc`, decrypt it first using the backup encryption key before restore. Verify the decrypted file's integrity.
**Files affected**: `scripts/verify-backup.sh`
**Depends on**: T004
**Status**: [x]

### T006: Add verification to CI scan
**Description**: Update `scripts/ci-scan.sh` to run `scripts/verify-backup.sh` against the most recent backup as part of the CI pipeline.
**Files affected**: `scripts/ci-scan.sh`
**Depends on**: T004
**Status**: [x]

---

## Phase 3: OCP Dashboard

### T007: Create backup-status.php page
**Description**: Create `web/backup-status.php` that scans the backup directory, reads `.meta.json` files, and displays a table with backup history, sizes, verification status, and retention countdown.
**Files affected**: `web/backup-status.php`
**Depends on**: T003
**Status**: [x]

### T008: Add backup status to role-nav
**Description**: Add a "Backup Status" link under the Administration section in `web/common/role-nav.php` for admin/devops roles.
**Files affected**: `web/common/role-nav.php`
**Depends on**: T007
**Status**: [x]

---

## Phase 4: Prometheus Metrics & Alerting

### T009: Create backup metrics exporter
**Description**: Create `docker/backup-exporter/` or extend existing exporter to expose `tsisip_backup_last_verify_timestamp`, `tsisip_backup_verify_success`, `tsisip_backup_size_bytes`, `tsisip_backup_age_seconds`.
**Files affected**: `docker/backup-exporter/` or `docker/prometheus/`
**Depends on**: T004
**Status**: [x]

### T010: Add Alertmanager rules
**Description**: Add alert rules to `docker/prometheus/alert-rules.yml` for backup verification failure and backup age exceeding retention threshold.
**Files affected**: `docker/prometheus/alert-rules.yml`
**Depends on**: T009
**Status**: [x]

---

## Phase 5: DR Drill Automation

### T011: Create dr-drill.sh script
**Description**: Create `scripts/dr-drill.sh` that stops the current stack, restores the latest verified backup to a fresh PostgreSQL container, starts a minimal stack, runs smoke tests, and produces a report with RTO measurement.
**Files affected**: `scripts/dr-drill.sh`
**Depends on**: T004
**Status**: [x]

### T012: Schedule DR drill via cron
**Description**: Add a cron job template or systemd timer configuration to run `scripts/dr-drill.sh` monthly.
**Files affected**: `deploy/ansible/` or `docker/` or docs
**Depends on**: T011
**Status**: [x]

---

## Phase 6: Testing & Documentation

### T013: Create integration tests
**Description**: Create `tests/integration/test_backup_verification.py` that tests backup generation, checksum validation, restore verification, and dashboard rendering.
**Files affected**: `tests/integration/test_backup_verification.py`
**Depends on**: T004, T007
**Status**: [x]

### T014: Update operator runbook
**Description**: Update `docs/TSiSIP-OPERATOR-RUNBOOK.md` with procedures for: interpreting backup verification failures, running manual DR drills, and restoring from backup in production.
**Files affected**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`
**Depends on**: T011
**Status**: [x]

### T015: Update CHANGELOG
**Description**: Add Feature 032 entry to `docs/CHANGELOG-2026-05.md`.
**Files affected**: `docs/CHANGELOG-2026-05.md`
**Depends on**: T013
**Status**: [x]
