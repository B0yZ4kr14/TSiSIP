#!/bin/bash
set -euo pipefail

# TSiSIP Offsite Replication Script
# Syncs backups to S3-compatible storage with bandwidth throttling

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
RCLONE_CONFIG="${RCLONE_CONFIG:-/etc/rclone/rclone.conf}"
REMOTE_NAME="${RCLONE_REMOTE_NAME:-remote}"
REMOTE_PATH="${RCLONE_REMOTE_PATH:-tsisip-backups}"
BW_LIMIT="${RCLONE_BW_LIMIT:-50M}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Check rclone config
if [ ! -f "$RCLONE_CONFIG" ]; then
    log "WARNING: rclone config not found at $RCLONE_CONFIG, skipping replication"
    exit 0
fi

log "Starting replication to ${REMOTE_NAME}:${REMOTE_PATH}"

# Sync daily backups
log "Syncing daily backups..."
rclone sync \
    --config "$RCLONE_CONFIG" \
    --bwlimit "$BW_LIMIT" \
    --checksum \
    --transfers 4 \
    --checkers 8 \
    "${BACKUP_DIR}/" "${REMOTE_NAME}:${REMOTE_PATH}/daily/" 2>/dev/null || {
    log "ERROR: Backup replication failed"
    exit 1
}

# Sync WAL segments
log "Syncing WAL segments..."
rclone sync \
    --config "$RCLONE_CONFIG" \
    --bwlimit "$BW_LIMIT" \
    --checksum \
    --transfers 8 \
    --checkers 16 \
    "${WAL_DIR}/" "${REMOTE_NAME}:${REMOTE_PATH}/wal/" 2>/dev/null || {
    log "ERROR: WAL replication failed"
    exit 1
}

log "Replication completed successfully"
exit 0
