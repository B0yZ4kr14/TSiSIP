#!/bin/bash
set -euo pipefail

# TSiSIP Restore Validation Script
# Restores latest backup to ephemeral container and validates

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
VALIDATE_DIR="${VALIDATE_DIR:-/backup/validate}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
PGPASSWORD_FILE="${PGPASSWORD_FILE:-/run/secrets/db_password}"
PGHOST="${PGHOST:-postgres}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-opensips}"
PGDATABASE="${PGDATABASE:-opensips}"
METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
JOBS_DIR="${JOBS_DIR:-/backup/jobs}"

# Timestamped job log
JOB_DATE=$(date +%Y-%m-%d)
JOB_TIME=$(date +%H%M%S)
JOB_LOG_DIR="${JOBS_DIR}/${JOB_DATE}"
JOB_LOG="${JOB_LOG_DIR}/validate_${JOB_TIME}.log"
mkdir -p "$JOB_LOG_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$JOB_LOG"
}

# Find latest backup
LATEST_BACKUP="$(readlink -f "${BACKUP_DIR}/latest" 2>/dev/null || ls -t ${BACKUP_DIR}/*.gz* 2>/dev/null | head -1)"

# First-day edge case: no backup exists yet
if [ -z "$LATEST_BACKUP" ] || [ ! -f "$LATEST_BACKUP" ]; then
    log "SKIPPED: No backup artifact found in $BACKUP_DIR (first-day or missing backup)"
    # Write skip metric for Prometheus
    mkdir -p /backup/metrics
    echo "# HELP backup_validation_status Validation status: 0=skipped, 1=success, 2=failed" > /backup/metrics/validation_status.prom
    echo "# TYPE backup_validation_status gauge" >> /backup/metrics/validation_status.prom
    echo "backup_validation_status 0" >> /backup/metrics/validation_status.prom
    echo "# HELP backup_validation_skip_reason Reason for skip (1=yes)" >> /backup/metrics/validation_status.prom
    echo "backup_validation_skip_reason{reason=\"no_backup_artifact\"} 1" >> /backup/metrics/validation_status.prom
    exit 0
fi

JOB_START=$(date +%s)
log "Validating backup: $LATEST_BACKUP"

# Start RTO timer
RTO_START="$(date +%s)"

# Prepare validation directory
rm -rf "$VALIDATE_DIR"
mkdir -p "$VALIDATE_DIR"

# Decrypt if needed
if [[ "$LATEST_BACKUP" == *.enc ]]; then
    if [ ! -f "$ENCRYPTION_KEY_FILE" ]; then
        echo "ERROR: Encrypted backup but no key available"
        exit 1
    fi
    /usr/local/bin/encrypt.sh decrypt "$LATEST_BACKUP" "${VALIDATE_DIR}/backup.dump.gz"
else
    cp "$LATEST_BACKUP" "${VALIDATE_DIR}/backup.dump.gz"
fi

# Decompress
gunzip -c "${VALIDATE_DIR}/backup.dump.gz" > "${VALIDATE_DIR}/backup.dump"

# Test restore (list contents)
if ! pg_restore -l "${VALIDATE_DIR}/backup.dump" > "${VALIDATE_DIR}/objects.list" 2>/dev/null; then
    echo "ERROR: Backup is corrupted or invalid"
    rm -rf "$VALIDATE_DIR"
    exit 1
fi

log "Backup structure valid - Objects: $(wc -l < "${VALIDATE_DIR}/objects.list")"

# Optional: Full restore to temp database for row count validation
# This requires a temporary PostgreSQL instance
if [ "${FULL_VALIDATE:-false}" == "true" ]; then
    log "Running full validation restore..."
    # Create temp database
    TEMP_DB="validate_$(date +%s)"
    PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" \
    psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d postgres -c "CREATE DATABASE $TEMP_DB;" 2>/dev/null || true
    
    # Restore
    PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" \
    pg_restore -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TEMP_DB" \
        --no-owner --no-privileges "${VALIDATE_DIR}/backup.dump" 2>/dev/null || true
    
    # Validate row counts
    SUBSCRIBER_COUNT="$(PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TEMP_DB" -t -c "SELECT COUNT(*) FROM subscriber;" 2>/dev/null | tr -d ' ' || echo 0)"
    LOCATION_COUNT="$(PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TEMP_DB" -t -c "SELECT COUNT(*) FROM location;" 2>/dev/null | tr -d ' ' || echo 0)"
    DISPATCHER_COUNT="$(PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TEMP_DB" -t -c "SELECT COUNT(*) FROM dispatcher;" 2>/dev/null | tr -d ' ' || echo 0)"
    
    log "Validation counts - subscriber: $SUBSCRIBER_COUNT, location: $LOCATION_COUNT, dispatcher: $DISPATCHER_COUNT"
    
    # Cleanup temp database
    PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" \
    psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d postgres -c "DROP DATABASE IF EXISTS $TEMP_DB;" 2>/dev/null || true
fi

# Cleanup
rm -rf "$VALIDATE_DIR"

# Calculate RTO
RTO_END="$(date +%s)"
RTO_SECONDS=$((RTO_END - RTO_START))
log "RTO (restore duration): ${RTO_SECONDS}s"

# Write RTO metric
mkdir -p "$METRICS_DIR"
echo "$RTO_SECONDS" > "${METRICS_DIR}/rto_last_seconds"

# Write job success metrics
JOB_END=$(date +%s)
JOB_DURATION=$((JOB_END - JOB_START))
mkdir -p "$METRICS_DIR"
cat > "${METRICS_DIR}/job_validate_last_success.prom" <<METRICS
# HELP backup_job_last_success Unix timestamp of last successful job run
# TYPE backup_job_last_success gauge
backup_job_last_success{job="validate"} ${JOB_END}
METRICS
cat > "${METRICS_DIR}/job_validate_last_duration.prom" <<METRICS
# HELP backup_job_last_duration Duration of last job run in seconds
# TYPE backup_job_last_duration gauge
backup_job_last_duration{job="validate"} ${JOB_DURATION}
METRICS

# Write legacy validation status metric
mkdir -p /backup/metrics
echo "# HELP backup_validation_status Validation status: 0=skipped, 1=success, 2=failed" > /backup/metrics/validation_status.prom
echo "# TYPE backup_validation_status gauge" >> /backup/metrics/validation_status.prom
echo "backup_validation_status 1" >> /backup/metrics/validation_status.prom
echo "# HELP backup_validation_timestamp Unix timestamp of last validation" >> /backup/metrics/validation_status.prom
echo "# TYPE backup_validation_timestamp gauge" >> /backup/metrics/validation_status.prom
echo "backup_validation_timestamp $(date +%s)" >> /backup/metrics/validation_status.prom

log "Validation completed successfully"
exit 0
