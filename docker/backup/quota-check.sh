#!/bin/bash
set -euo pipefail

# TSiSIP Storage Quota Monitor
# Checks backup storage usage and triggers alerts/purge if thresholds exceeded

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
BACKUP_QUOTA_GB="${BACKUP_QUOTA_GB:-100}"
QUOTA_WARN_PERCENT="${QUOTA_WARN_PERCENT:-80}"
QUOTA_CRIT_PERCENT="${QUOTA_CRIT_PERCENT:-95}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Calculate usage in bytes
BACKUP_USAGE="$(du -sb "$BACKUP_DIR" 2>/dev/null | cut -f1 || echo 0)"
WAL_USAGE="$(du -sb "$WAL_DIR" 2>/dev/null | cut -f1 || echo 0)"
TOTAL_USAGE=$((BACKUP_USAGE + WAL_USAGE))

# Convert quota to bytes
QUOTA_BYTES=$((BACKUP_QUOTA_GB * 1024 * 1024 * 1024))

# Calculate percentage
if [ "$QUOTA_BYTES" -gt 0 ]; then
    USED_PERCENT="$(awk "BEGIN {printf \"%.2f\", ($TOTAL_USAGE / $QUOTA_BYTES) * 100}")"
    USED_PERCENT_INT="$(echo "$USED_PERCENT" | cut -d. -f1)"
else
    USED_PERCENT="0.00"
    USED_PERCENT_INT=0
fi

# Ensure metrics directory exists
mkdir -p "$METRICS_DIR"

# Write Prometheus metric
cat > "${METRICS_DIR}/quota_usage.prom" <<EOF
# HELP backup_quota_used_percent Percentage of backup quota used
# TYPE backup_quota_used_percent gauge
backup_quota_used_percent ${USED_PERCENT}
# HELP backup_quota_bytes_total Total backup quota in bytes
# TYPE backup_quota_bytes_total gauge
backup_quota_bytes_total ${QUOTA_BYTES}
# HELP backup_usage_bytes Current backup usage in bytes
# TYPE backup_usage_bytes gauge
backup_usage_bytes ${TOTAL_USAGE}
EOF

log "Storage usage: ${USED_PERCENT}% (${TOTAL_USAGE} bytes / ${QUOTA_BYTES} bytes)"

# Trigger accelerated purge if above warning threshold
if [ "$USED_PERCENT_INT" -ge "$QUOTA_CRIT_PERCENT" ]; then
    log "CRITICAL: Storage usage ${USED_PERCENT}% exceeds critical threshold ${QUOTA_CRIT_PERCENT}%"
    # Run purge immediately with aggressive retention (half normal)
    BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
    AGGRESSIVE_RETENTION=$((BACKUP_RETENTION_DAYS / 2))
    if [ "$AGGRESSIVE_RETENTION" -lt 3 ]; then
        AGGRESSIVE_RETENTION=3
    fi
    log "Triggering aggressive purge with ${AGGRESSIVE_RETENTION} days retention"
    BACKUP_RETENTION_DAYS="$AGGRESSIVE_RETENTION" /usr/local/bin/purge.sh || true
    exit 2
elif [ "$USED_PERCENT_INT" -ge "$QUOTA_WARN_PERCENT" ]; then
    log "WARNING: Storage usage ${USED_PERCENT}% exceeds warning threshold ${QUOTA_WARN_PERCENT}%"
    # Run normal purge
    /usr/local/bin/purge.sh || true
    exit 1
fi

exit 0
