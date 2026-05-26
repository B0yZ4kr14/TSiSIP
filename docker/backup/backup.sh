#!/bin/bash
set -euo pipefail

# TSiSIP PostgreSQL Logical Backup Script
# Creates compressed, encrypted pg_dump backups

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="opensips_${TIMESTAMP}.dump"
BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
JOBS_DIR="${JOBS_DIR:-/backup/jobs}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
PGPASSWORD_FILE="${PGPASSWORD_FILE:-/run/secrets/db_password}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
# Encryption is mandatory in all environments per TSiSIP security policy.

# Ensure directories exist
mkdir -p "$BACKUP_DIR" "$WAL_DIR"
mkdir -p /tmp/backup
umask 077

# Timestamped job log
JOB_DATE=$(date +%Y-%m-%d)
JOB_TIME=$(date +%H%M%S)
JOB_LOG_DIR="${JOBS_DIR}/${JOB_DATE}"
JOB_LOG="${JOB_LOG_DIR}/backup_${JOB_TIME}.log"
mkdir -p "$JOB_LOG_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$JOB_LOG"
}

JOB_START=$(date +%s)

if [ ! -f "$PGPASSWORD_FILE" ] || [ ! -s "$PGPASSWORD_FILE" ]; then
    log "ERROR: PostgreSQL password file missing or empty: $PGPASSWORD_FILE"
    exit 1
fi

if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
    log "ERROR: Encryption key missing or empty: $ENCRYPTION_KEY_FILE"
    exit 1
fi

log "Starting backup: $BACKUP_FILE"

# Create logical backup with custom format and compression
# Use --lock-wait-timeout to avoid long locks
# Use REPEATABLE READ for consistency
# Throttle I/O to avoid impacting production
PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" \
PGOPTIONS="-c default_transaction_isolation=repeatable\\ read" \
nice -n 10 ionice -c2 -n7 \
pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
    -Fc -Z9 \
    --lock-wait-timeout=5000 \
    -f "/tmp/backup/${BACKUP_FILE}"

log "Backup created: /tmp/backup/${BACKUP_FILE}"

# Compress with gzip
nice -n 10 gzip -c "/tmp/backup/${BACKUP_FILE}" > "${BACKUP_DIR}/${BACKUP_FILE}.gz"
rm -f "/tmp/backup/${BACKUP_FILE}"

log "Backup compressed: ${BACKUP_DIR}/${BACKUP_FILE}.gz"

# Encrypt backup (mandatory)
if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
    log "ERROR: Encryption key missing or empty: $ENCRYPTION_KEY_FILE"
    exit 1
fi

/usr/local/bin/encrypt.sh encrypt "${BACKUP_DIR}/${BACKUP_FILE}.gz" "${BACKUP_DIR}/${BACKUP_FILE}.gz.enc"
rm -f "${BACKUP_DIR}/${BACKUP_FILE}.gz"
log "Backup encrypted: ${BACKUP_DIR}/${BACKUP_FILE}.gz.enc"
ln -sfn "${BACKUP_FILE}.gz.enc" "${BACKUP_DIR}/latest"

log "Backup completed successfully"

# Write job success metrics
JOB_END=$(date +%s)
JOB_DURATION=$((JOB_END - JOB_START))
mkdir -p "$METRICS_DIR"
cat > "${METRICS_DIR}/job_backup_last_success.prom" <<METRICS
# HELP backup_job_last_success Unix timestamp of last successful job run
# TYPE backup_job_last_success gauge
backup_job_last_success{job="daily"} ${JOB_END}
METRICS
cat > "${METRICS_DIR}/job_backup_last_duration.prom" <<METRICS
# HELP backup_job_last_duration Duration of last job run in seconds
# TYPE backup_job_last_duration gauge
backup_job_last_duration{job="daily"} ${JOB_DURATION}
METRICS

# ---------------------------------------------------------------------------
# T6.1 — Offsite replication (Wave 2)
# After a successful local encrypted backup, immediately sync to the
# S3-compatible remote so that the offsite copy exists within the
# replication window.  replicate.sh exits 0 gracefully when no remote
# is configured, so this is safe to run unconditionally.
# ---------------------------------------------------------------------------
if [ -x /usr/local/bin/replicate.sh ]; then
    log "Triggering offsite replication..."
    /usr/local/bin/replicate.sh || log "WARNING: Offsite replication failed (see replicate.log)"
fi

exit 0
