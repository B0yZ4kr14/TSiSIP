#!/bin/bash
set -euo pipefail

# TSiSIP RPO Monitor
# Queries pg_stat_archiver lag and exposes metric for Prometheus

PGHOST="${PGHOST:-postgres}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-opensips}"
PGDATABASE="${PGDATABASE:-opensips}"
PGPASSWORD_FILE="${PGPASSWORD_FILE:-/run/secrets/db_password}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
RPO_THRESHOLD="${RPO_THRESHOLD:-300}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Ensure metrics directory exists
mkdir -p "$METRICS_DIR"

# Query pg_stat_archiver and the current WAL segment.
DB_PASS=""
if [ -f "$PGPASSWORD_FILE" ] && [ -s "$PGPASSWORD_FILE" ]; then
    DB_PASS="$(cat "$PGPASSWORD_FILE")"
else
    log "ERROR: PostgreSQL password file missing or empty: $PGPASSWORD_FILE"
    exit 1
fi

ARCHIVER_STATE="$(PGPASSWORD="$DB_PASS" \
    psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
    -t -A -F '|' -c "SELECT pg_walfile_name(pg_current_wal_lsn()), COALESCE(last_archived_wal, ''), COALESCE(EXTRACT(EPOCH FROM last_archived_time), 0) FROM pg_stat_archiver;" 2>/dev/null || echo "||0")"

CURRENT_WAL="$(echo "$ARCHIVER_STATE" | cut -d'|' -f1)"
LAST_ARCHIVED_WAL="$(echo "$ARCHIVER_STATE" | cut -d'|' -f2)"
LAST_ARCHIVED_TIME="$(echo "$ARCHIVER_STATE" | cut -d'|' -f3)"

CURRENT_TIME="$(date +%s)"

if [ -n "$CURRENT_WAL" ] && [ "$CURRENT_WAL" = "$LAST_ARCHIVED_WAL" ]; then
    RPO_LAG=0
elif [ "$LAST_ARCHIVED_TIME" = "0" ] || [ -z "$LAST_ARCHIVED_TIME" ]; then
    RPO_LAG="$RPO_THRESHOLD"
    log "WARNING: Could not determine last archived time, assuming max lag"
else
    RPO_LAG=$((CURRENT_TIME - $(echo "$LAST_ARCHIVED_TIME" | cut -d. -f1)))
fi

if [ "$RPO_LAG" -gt "$RPO_THRESHOLD" ]; then
    log "RPO lag ${RPO_LAG}s exceeds threshold ${RPO_THRESHOLD}s; current_wal=${CURRENT_WAL:-unknown} last_archived_wal=${LAST_ARCHIVED_WAL:-none}; forcing WAL switch to verify archiver"
    PGPASSWORD="$DB_PASS" \
        psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
        -t -A -c "SELECT pg_switch_wal();" >/dev/null 2>&1 || true
    sleep 2

    ARCHIVER_STATE="$(PGPASSWORD="$DB_PASS" \
        psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
        -t -A -F '|' -c "SELECT pg_walfile_name(pg_current_wal_lsn()), COALESCE(last_archived_wal, ''), COALESCE(EXTRACT(EPOCH FROM last_archived_time), 0) FROM pg_stat_archiver;" 2>/dev/null || echo "||0")"
    CURRENT_WAL="$(echo "$ARCHIVER_STATE" | cut -d'|' -f1)"
    LAST_ARCHIVED_WAL="$(echo "$ARCHIVER_STATE" | cut -d'|' -f2)"
    LAST_ARCHIVED_TIME="$(echo "$ARCHIVER_STATE" | cut -d'|' -f3)"
    CURRENT_TIME="$(date +%s)"

    if [ -n "$CURRENT_WAL" ] && [ "$CURRENT_WAL" = "$LAST_ARCHIVED_WAL" ]; then
        RPO_LAG=0
    elif [ "$LAST_ARCHIVED_TIME" = "0" ] || [ -z "$LAST_ARCHIVED_TIME" ]; then
        RPO_LAG="$RPO_THRESHOLD"
    else
        RPO_LAG=$((CURRENT_TIME - $(echo "$LAST_ARCHIVED_TIME" | cut -d. -f1)))
    fi
fi

# Write Prometheus metric
cat > "${METRICS_DIR}/rpo_lag_seconds.prom" <<EOF
# HELP backup_rpo_lag_seconds WAL archiving lag in seconds
# TYPE backup_rpo_lag_seconds gauge
backup_rpo_lag_seconds ${RPO_LAG}
# HELP backup_current_wal_info Current and latest archived WAL segment
# TYPE backup_current_wal_info gauge
backup_current_wal_info{current_wal="${CURRENT_WAL:-unknown}",last_archived_wal="${LAST_ARCHIVED_WAL:-none}"} 1
# HELP backup_rpo_threshold_seconds RPO threshold in seconds
# TYPE backup_rpo_threshold_seconds gauge
backup_rpo_threshold_seconds ${RPO_THRESHOLD}
EOF

if [ "$RPO_LAG" -gt "$RPO_THRESHOLD" ]; then
    log "ALERT: RPO lag ${RPO_LAG}s exceeds threshold ${RPO_THRESHOLD}s; current_wal=${CURRENT_WAL:-unknown} last_archived_wal=${LAST_ARCHIVED_WAL:-none}"
    exit 1
fi

log "RPO lag: ${RPO_LAG}s (threshold: ${RPO_THRESHOLD}s, current_wal=${CURRENT_WAL:-unknown}, last_archived_wal=${LAST_ARCHIVED_WAL:-none})"
exit 0
