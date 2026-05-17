#!/bin/bash
set -euo pipefail

# TSiSIP PostgreSQL Logical Backup Script
# Creates compressed, encrypted pg_dump backups

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="opensips_${TIMESTAMP}.dump"
BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"

# Ensure directories exist
mkdir -p "$BACKUP_DIR" "$WAL_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

log "Starting backup: $BACKUP_FILE"

# Create logical backup with custom format and compression
# Use --lock-wait-timeout to avoid long locks
# Use REPEATABLE READ for consistency
PGPASSWORD="$(cat "$PGPASSWORD_FILE" 2>/dev/null || echo '')" \
pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" \
    -Fc -Z9 \
    --lock-wait-timeout=5000 \
    --transaction-isolation=repeatable-read \
    -f "/tmp/backup/${BACKUP_FILE}"

log "Backup created: /tmp/backup/${BACKUP_FILE}"

# Compress with gzip
gzip -c "/tmp/backup/${BACKUP_FILE}" > "${BACKUP_DIR}/${BACKUP_FILE}.gz"
rm -f "/tmp/backup/${BACKUP_FILE}"

log "Backup compressed: ${BACKUP_DIR}/${BACKUP_FILE}.gz"

# Encrypt if key is available
if [ -f "$ENCRYPTION_KEY_FILE" ] && [ -s "$ENCRYPTION_KEY_FILE" ]; then
    /usr/local/bin/encrypt.sh encrypt "${BACKUP_DIR}/${BACKUP_FILE}.gz" "${BACKUP_DIR}/${BACKUP_FILE}.gz.enc"
    rm -f "${BACKUP_DIR}/${BACKUP_FILE}.gz"
    log "Backup encrypted: ${BACKUP_DIR}/${BACKUP_FILE}.gz.enc"
else
    log "WARNING: No encryption key found, backup stored unencrypted"
fi

# Update latest symlink
ln -sf "${BACKUP_FILE}.gz.enc" "${BACKUP_DIR}/latest" 2>/dev/null || \
ln -sf "${BACKUP_FILE}.gz" "${BACKUP_DIR}/latest" 2>/dev/null || true

log "Backup completed successfully"
exit 0
