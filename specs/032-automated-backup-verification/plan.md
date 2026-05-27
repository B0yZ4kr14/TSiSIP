# Plan: Automated Backup Verification & Disaster Recovery Testing

## Architecture

- **Backup phase**: `scripts/backup-db.sh` enhanced to generate `.sha256` checksums
- **Verification phase**: `scripts/verify-backup.sh` restores to temp container, runs validation queries
- **Dashboard**: `web/backup-status.php` reads backup directory metadata and verification logs
- **Metrics**: Node.js/Python exporter or shell-based textfile collector for Prometheus
- **Alerting**: Alertmanager rule in `docker/prometheus/alert-rules.yml`
- **DR Drill**: `scripts/dr-drill.sh` orchestrates full restore + smoke test

## Files

- `scripts/backup-db.sh` — Enhanced with checksum generation
- `scripts/verify-backup.sh` — New: restore verification
- `scripts/dr-drill.sh` — New: monthly DR drill
- `web/backup-status.php` — New: OCP admin dashboard
- `docker/prometheus/alert-rules.yml` — Add backup verification alerts
- `tests/integration/test_backup_verification.py` — New: automated tests
