#!/bin/bash
# TSiSIP Backup Metrics Writer (container variant)
# Complements metrics-exporter.sh by writing detailed Prometheus metrics
# after each backup job. Operates on the container's backup directory.
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"

LAST_TIMESTAMP=0
VERIFY_SUCCESS=1
SIZE_BYTES=0
BACKUP_AGE=0
BACKUP_COUNT=0

# Find latest backup (supports both encrypted and unencrypted)
LATEST_BACKUP=$(find "$BACKUP_DIR" -maxdepth 1 \( -name 'opensips_*.dump.gz.enc' -o -name 'opensips_*.dump.gz' \) -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2- || true)

if [[ -n "$LATEST_BACKUP" && -f "$LATEST_BACKUP" ]]; then
    LAST_TIMESTAMP=$(stat -c %Y "$LATEST_BACKUP")
    SIZE_BYTES=$(stat -c %s "$LATEST_BACKUP")
    BACKUP_AGE=$(($(date +%s) - LAST_TIMESTAMP))

    # Check verification status from validation_status.prom
    if [[ -f "$METRICS_DIR/validation_status.prom" ]]; then
        VALID_STATUS=$(grep "^backup_validation_status " "$METRICS_DIR/validation_status.prom" 2>/dev/null | awk '{print $2}' || echo 0)
        if [[ "$VALID_STATUS" == "1" ]]; then
            VERIFY_SUCCESS=1
        else
            VERIFY_SUCCESS=0
        fi
    fi
fi

BACKUP_COUNT=$(find "$BACKUP_DIR" -maxdepth 1 \( -name 'opensips_*.dump.gz.enc' -o -name 'opensips_*.dump.gz' \) -type f 2>/dev/null | wc -l || echo 0)

mkdir -p "$METRICS_DIR"
cat > "$METRICS_DIR/backup_detailed_metrics.prom" <<EOF
# HELP tsisip_backup_last_timestamp Unix timestamp of the latest backup
# TYPE tsisip_backup_last_timestamp gauge
tsisip_backup_last_timestamp $LAST_TIMESTAMP

# HELP tsisip_backup_verify_success 1 if latest backup verified successfully, 0 otherwise
# TYPE tsisip_backup_verify_success gauge
tsisip_backup_verify_success $VERIFY_SUCCESS

# HELP tsisip_backup_size_bytes Size of the latest backup in bytes
# TYPE tsisip_backup_size_bytes gauge
tsisip_backup_size_bytes $SIZE_BYTES

# HELP tsisip_backup_age_seconds Age of the latest backup in seconds
# TYPE tsisip_backup_age_seconds gauge
tsisip_backup_age_seconds $BACKUP_AGE

# HELP tsisip_backup_total_count Total number of backups in the backup directory
# TYPE tsisip_backup_total_count gauge
tsisip_backup_total_count $BACKUP_COUNT
EOF
