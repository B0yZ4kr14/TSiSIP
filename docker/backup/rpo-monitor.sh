#!/bin/bash
set -euo pipefail

# TSiSIP RPO Monitor
# Queries pg_stat_archiver lag and exposes metric for Prometheus

PGHOST="${PGHOST:-postgres}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-opensips}"
PGDATABASE="${PGDATABASE:-opensips}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
RPO_THRESHOLD="${RPO_THRESHOLD:-300}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Ensure metrics directory exists
mkdir -p "$METRICS_DIR"

# Query pg_stat_archiver for last archived time
DB_PASS=""
if [ -f /run/secrets/db_password ] && [ -s /run/secrets/db_password ]; then
    DB_PASS="$(cat /run/secrets/db_password)"
fi

LAST_ARCHIVED_TIME="$(PGPASSWORD="$DB_PASS" \
    psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
    -t -A -c "SELECT COALESCE(EXTRACT(EPOCH FROM last_archived_time), 0) FROM pg_stat_archiver;" 2>/dev/null | awk '{print $1}' || echo 0)"

CURRENT_TIME="$(date +%s)"

if [ "$LAST_ARCHIVED_TIME" = "0" ] || [ -z "$LAST_ARCHIVED_TIME" ]; then
    RPO_LAG="$RPO_THRESHOLD"
    log "WARNING: Could not determine last archived time, assuming max lag"
else
    RPO_LAG=$((CURRENT_TIME - $(echo "$LAST_ARCHIVED_TIME" | cut -d. -f1)))
fi

# Write Prometheus metric
cat > "${METRICS_DIR}/rpo_lag_seconds.prom" <<EOF
# HELP backup_rpo_lag_seconds WAL archiving lag in seconds
# TYPE backup_rpo_lag_seconds gauge
backup_rpo_lag_seconds ${RPO_LAG}
# HELP backup_rpo_threshold_seconds RPO threshold in seconds
# TYPE backup_rpo_threshold_seconds gauge
backup_rpo_threshold_seconds ${RPO_THRESHOLD}
EOF

if [ "$RPO_LAG" -gt "$RPO_THRESHOLD" ]; then
    log "ALERT: RPO lag ${RPO_LAG}s exceeds threshold ${RPO_THRESHOLD}s"
    exit 1
fi

log "RPO lag: ${RPO_LAG}s (threshold: ${RPO_THRESHOLD}s)"
exit 0
