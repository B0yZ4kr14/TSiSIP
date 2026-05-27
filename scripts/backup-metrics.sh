#!/usr/bin/env bash
# TSiSIP Backup Metrics Exporter for Prometheus
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_DIR}/backups"
OUTPUT_FILE="${PROJECT_DIR}/docker/prometheus/backup-metrics.prom"

# Default values
LAST_TIMESTAMP=0
VERIFY_SUCCESS=1
SIZE_BYTES=0
BACKUP_AGE=0

# Find latest backup
LATEST_BACKUP=$(ls -t "${BACKUP_DIR}"/tsisip_db_*.sql.gz 2>/dev/null | head -1 || true)

if [[ -n "$LATEST_BACKUP" && -f "$LATEST_BACKUP" ]]; then
    LAST_TIMESTAMP=$(stat -c %Y "$LATEST_BACKUP")
    SIZE_BYTES=$(stat -c %s "$LATEST_BACKUP")
    BACKUP_AGE=$(($(date +%s) - LAST_TIMESTAMP))
    
    # Check verification status from metadata
    META_FILE="${LATEST_BACKUP}.meta.json"
    if [[ -f "$META_FILE" ]]; then
        VERIFY_STATUS=$(python3 -c "
import json, sys
try:
    with open('$META_FILE') as f:
        data = json.load(f)
    print(1 if data.get('verify_status') == 'pass' else 0)
except:
    print(0)
" 2>/dev/null || echo 0)
        VERIFY_SUCCESS=$VERIFY_STATUS
    fi
fi

# Write Prometheus metrics
cat > "$OUTPUT_FILE" <<EOF
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
tsisip_backup_total_count $(ls "${BACKUP_DIR}"/tsisip_db_*.sql.gz 2>/dev/null | wc -l)
EOF

echo "Metrics written to $OUTPUT_FILE"
