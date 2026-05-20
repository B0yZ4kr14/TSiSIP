#!/bin/bash
set -euo pipefail

# TSiSIP PostgreSQL Logical Backup Script
# Creates compressed, encrypted pg_dump backups

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="opensips_${TIMESTAMP}.dump"
BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
PGPASSWORD_FILE="${PGPASSWORD_FILE:-/run/secrets/db_password}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
# Encryption is mandatory in all environments per TSiSIP security policy.
# The ALLOW_UNENCRYPTED_BACKUPS opt-out has been removed (brownfield B8).

# Ensure directories exist
mkdir -p "$BACKUP_DIR" "$WAL_DIR"
mkdir -p /tmp/backup
umask 077

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

if [ ! -f "$PGPASSWORD_FILE" ] || [ ! -s "$PGPASSWORD_FILE" ]; then
    log "ERROR: PostgreSQL password file missing or empty: $PGPASSWORD_FILE"
    exit 1
fi

if [ "$ALLOW_UNENCRYPTED_BACKUPS" != "true" ] && { [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; }; then
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
gzip -c "/tmp/backup/${BACKUP_FILE}" > "${BACKUP_DIR}/${BACKUP_FILE}.gz"
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
