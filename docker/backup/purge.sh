#!/bin/bash
set -euo pipefail

# TSiSIP Backup Retention Policy Engine
# Purges old backups and WAL segments

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
JOBS_DIR="${JOBS_DIR:-/backup/jobs}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
WAL_RETENTION_DAYS="${WAL_RETENTION_DAYS:-37}"
LGPD_RETENTION_DAYS="${LGPD_RETENTION_DAYS:-365}"

# Timestamped job log
JOB_DATE=$(date +%Y-%m-%d)
JOB_TIME=$(date +%H%M%S)
JOB_LOG_DIR="${JOBS_DIR}/${JOB_DATE}"
JOB_LOG="${JOB_LOG_DIR}/purge_${JOB_TIME}.log"
mkdir -p "$JOB_LOG_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$JOB_LOG"
}

# LGPD compliance: never purge backups younger than LGPD retention window
if [ "$BACKUP_RETENTION_DAYS" -lt "$LGPD_RETENTION_DAYS" ]; then
    EFFECTIVE_BACKUP_RETENTION="$LGPD_RETENTION_DAYS"
    log "LGPD override: backup retention extended from ${BACKUP_RETENTION_DAYS}d to ${LGPD_RETENTION_DAYS}d"
else
    EFFECTIVE_BACKUP_RETENTION="$BACKUP_RETENTION_DAYS"
fi

JOB_START=$(date +%s)
log "Starting purge - Effective backup retention: ${EFFECTIVE_BACKUP_RETENTION}d (LGPD=${LGPD_RETENTION_DAYS}d), WAL retention: ${WAL_RETENTION_DAYS}d"

# Find oldest backup to determine WAL retention cutoff
OLDEST_BACKUP="$(find "$BACKUP_DIR" -name '*.gz*' -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | head -1 | cut -d' ' -f2-)"
if [ -n "$OLDEST_BACKUP" ]; then
    OLDEST_BACKUP_DATE="$(stat -c %Y "$OLDEST_BACKUP" 2>/dev/null || echo 0)"
    WAL_CUTOFF_DATE=$((OLDEST_BACKUP_DATE - 86400 * 7))
else
    WAL_CUTOFF_DATE="$(date -d "-${WAL_RETENTION_DAYS} days" +%s)"
fi

# Purge old backups
BACKUP_DELETED=0
while IFS= read -r file; do
    [ -z "$file" ] && continue
    log "Deleting old backup: $file"
    rm -f "$file"
    BACKUP_DELETED=$((BACKUP_DELETED + 1))
done < <(find "$BACKUP_DIR" -name '*.gz*' -type f -mtime +"$EFFECTIVE_BACKUP_RETENTION" 2>/dev/null)

# Purge old WAL segments
WAL_DELETED=0
while IFS= read -r file; do
    [ -z "$file" ] && continue
    FILE_MTIME="$(stat -c %Y "$file" 2>/dev/null || echo 0)"
    if [ "$FILE_MTIME" -lt "$WAL_CUTOFF_DATE" ]; then
        log "Deleting old WAL: $file"
        rm -f "$file"
        WAL_DELETED=$((WAL_DELETED + 1))
    fi
done < <(find "$WAL_DIR" -name '*.gz*' -type f 2>/dev/null)

# Clean empty directories
find "$WAL_DIR" -type d -empty -delete 2>/dev/null || true

# Purge old OCP audit log entries
OCP_AUDIT_RETENTION_DAYS="${OCP_AUDIT_RETENTION_DAYS:-90}"
if command -v psql >/dev/null 2>&1 && [ -n "${PGHOST:-}" ] && [ -n "${PGDATABASE:-}" ] && [ -n "${PGUSER:-}" ]; then
    log "Purging OCP audit logs older than ${OCP_AUDIT_RETENTION_DAYS} days..."
    AUDIT_DELETED=$(psql -h "$PGHOST" -d "$PGDATABASE" -U "$PGUSER" -t -c \
        "SELECT ocp_audit_log_retention_purge(${OCP_AUDIT_RETENTION_DAYS});" 2>/dev/null || echo "0")
    log "Audit log purge completed - Rows deleted: $(echo "$AUDIT_DELETED" | tr -d ' ')"
else
    log "Skipping audit log purge: PostgreSQL connection unavailable"
fi

log "Purge completed - Backups deleted: $BACKUP_DELETED, WAL deleted: $WAL_DELETED"

# Write job success metrics
JOB_END=$(date +%s)
JOB_DURATION=$((JOB_END - JOB_START))
mkdir -p "$METRICS_DIR"
cat > "${METRICS_DIR}/job_purge_last_success.prom" <<METRICS
# HELP backup_job_last_success Unix timestamp of last successful job run
# TYPE backup_job_last_success gauge
backup_job_last_success{job="purge"} ${JOB_END}
METRICS
cat > "${METRICS_DIR}/job_purge_last_duration.prom" <<METRICS
# HELP backup_job_last_duration Duration of last job run in seconds
# TYPE backup_job_last_duration gauge
backup_job_last_duration{job="purge"} ${JOB_DURATION}
METRICS

exit 0
