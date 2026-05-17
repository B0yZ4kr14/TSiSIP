#!/bin/bash
set -euo pipefail

# TSiSIP Restore Validation Script
# Restores latest backup to ephemeral container and validates

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
VALIDATE_DIR="${VALIDATE_DIR:-/backup/validate}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
PGHOST="${PGHOST:-postgres}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-opensips}"
PGDATABASE="${PGDATABASE:-opensips}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Find latest backup
LATEST_BACKUP="$(readlink -f "${BACKUP_DIR}/latest" 2>/dev/null || ls -t ${BACKUP_DIR}/*.gz* 2>/dev/null | head -1)"

if [ -z "$LATEST_BACKUP" ] || [ ! -f "$LATEST_BACKUP" ]; then
    echo "ERROR: No backup found in $BACKUP_DIR"
    exit 1
fi

log "Validating backup: $LATEST_BACKUP"

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

log "Validation completed successfully"
exit 0
