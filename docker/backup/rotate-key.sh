#!/bin/bash
set -euo pipefail

# TSiSIP Encryption Key Rotation Script
# Re-encrypts recent backups with a new key from Docker secret

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"
NEW_KEY_FILE="${NEW_KEY_FILE:-/run/secrets/backup_encryption_key_new}"
ROTATION_DAYS="${ROTATION_DAYS:-7}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

usage() {
    echo "Usage: $0 [--dry-run]"
    echo "  --dry-run    List backups that would be rotated without modifying them"
    exit 1
}

DRY_RUN=false
if [ $# -gt 0 ]; then
    case "$1" in
        --dry-run) DRY_RUN=true ;;
        *) usage ;;
    esac
fi

# Verify new key exists
if [ ! -f "$NEW_KEY_FILE" ] || [ ! -s "$NEW_KEY_FILE" ]; then
    log "ERROR: New encryption key not found at $NEW_KEY_FILE"
    exit 1
fi

# Verify old key exists
if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
    log "ERROR: Current encryption key not found at $ENCRYPTION_KEY_FILE"
    exit 1
fi

NEW_KEY="$(cat "$NEW_KEY_FILE")"
OLD_KEY="$(cat "$ENCRYPTION_KEY_FILE")"

if [ "$NEW_KEY" = "$OLD_KEY" ]; then
    log "WARNING: New key is identical to old key, skipping rotation"
    exit 0
fi

ROTATED=0
FAILED=0

rotate_file() {
    local file="$1"
    local old_key="$2"
    local new_key="$3"
    local temp_decrypt="$4"
    local temp_reencrypt="$5"

    if ! openssl enc -aes-256-cbc -d -pbkdf2 -iter 10000 \
        -in "$file" -out "$temp_decrypt" -k "$old_key" 2>/dev/null; then
        return 1
    fi

    if ! openssl enc -aes-256-cbc -salt -pbkdf2 -iter 10000 \
        -in "$temp_decrypt" -out "$temp_reencrypt" -k "$new_key" 2>/dev/null; then
        return 1
    fi

    if ! openssl enc -aes-256-cbc -d -pbkdf2 -iter 10000 \
        -in "$temp_reencrypt" -out /dev/null -k "$new_key" 2>/dev/null; then
        return 1
    fi

    # Generate new HMAC
    openssl dgst -sha256 -hmac "$new_key" -binary "$temp_reencrypt" | \
        od -An -tx1 | tr -d ' \n' > "${temp_reencrypt}.hmac"

    mv "$temp_reencrypt" "$file"
    mv "${temp_reencrypt}.hmac" "${file}.hmac"
    rm -f "$temp_decrypt"
    return 0
}

# Find backups to rotate
while IFS= read -r backup_file; do
    [ -z "$backup_file" ] && continue
    [ ! -f "$backup_file" ] && continue
    [[ "$backup_file" != *.enc ]] && continue

    backup_name="$(basename "$backup_file")"

    if [ "$DRY_RUN" = true ]; then
        log "[DRY-RUN] Would rotate: $backup_name"
        ROTATED=$((ROTATED + 1))
        continue
    fi

    log "Rotating key for: $backup_name"

    TEMP_DECRYPT="/tmp/rotate_${backup_name}.dec"
    TEMP_REENCRYPT="/tmp/rotate_${backup_name}.re"

    if rotate_file "$backup_file" "$OLD_KEY" "$NEW_KEY" "$TEMP_DECRYPT" "$TEMP_REENCRYPT"; then
        log "Successfully rotated: $backup_name"
        ROTATED=$((ROTATED + 1))
    else
        log "ERROR: Failed to rotate $backup_name"
        rm -f "$TEMP_DECRYPT" "$TEMP_REENCRYPT" "${TEMP_REENCRYPT}.hmac"
        FAILED=$((FAILED + 1))
    fi
done < <(find "$BACKUP_DIR" -name '*.enc' -type f -mtime -"$ROTATION_DAYS" 2>/dev/null)

# Also rotate WAL segments
while IFS= read -r wal_file; do
    [ -z "$wal_file" ] && continue
    [ ! -f "$wal_file" ] && continue
    [[ "$wal_file" != *.enc ]] && continue

    wal_name="$(basename "$wal_file")"

    if [ "$DRY_RUN" = true ]; then
        log "[DRY-RUN] Would rotate WAL: $wal_name"
        ROTATED=$((ROTATED + 1))
        continue
    fi

    log "Rotating key for WAL: $wal_name"

    TEMP_DECRYPT="/tmp/rotate_${wal_name}.dec"
    TEMP_REENCRYPT="/tmp/rotate_${wal_name}.re"

    if rotate_file "$wal_file" "$OLD_KEY" "$NEW_KEY" "$TEMP_DECRYPT" "$TEMP_REENCRYPT"; then
        log "Successfully rotated WAL: $wal_name"
        ROTATED=$((ROTATED + 1))
    else
        log "ERROR: Failed to rotate WAL $wal_name"
        rm -f "$TEMP_DECRYPT" "$TEMP_REENCRYPT" "${TEMP_REENCRYPT}.hmac"
        FAILED=$((FAILED + 1))
    fi
done < <(find "$WAL_DIR" -name '*.enc' -type f -mtime -"$ROTATION_DAYS" 2>/dev/null)

log "Key rotation completed - Rotated: $ROTATED, Failed: $FAILED"

if [ "$FAILED" -gt 0 ]; then
    exit 1
fi

exit 0
