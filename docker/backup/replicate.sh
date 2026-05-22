#!/bin/bash
set -euo pipefail

# TSiSIP Offsite Replication Script
# Syncs encrypted backups and WAL segments to S3-compatible storage
# with bandwidth throttling to protect SIP traffic.
#
# Socratic Decision Log — T6.2 Bandwidth Throttling:
# Q: What is the minimum viable bandwidth limit?
# A: 5 Mbps (625 KB/s) is conservative for a VPS with 100 Mbps uplink,
#    leaving headroom for SIP signaling and RTP media.
# Hypothesis: "Replication completes without saturating VPS link"
# Falsification test: Replication runs concurrently with SIP traffic;
#    no packet loss observed (verify with ping -i 0.2 <gw> during sync).
#
# Required environment variables (see rclone.conf.tpl for remote config):
#   RCLONE_CONFIG          — Path to rendered rclone.conf
#   RCLONE_REMOTE_NAME     — rclone remote section name (default: remote)
#   RCLONE_REMOTE_PATH     — Remote path prefix / bucket (default: tsisip-backups)
#   RCLONE_BW_LIMIT        — Upload cap in rclone syntax (default: 625K = 5 Mbps)

BACKUP_DIR="${BACKUP_DIR:-/backup/daily}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
RCLONE_CONFIG="${RCLONE_CONFIG:-/etc/rclone/rclone.conf}"
REMOTE_NAME="${RCLONE_REMOTE_NAME:-remote}"
REMOTE_PATH="${RCLONE_REMOTE_PATH:-tsisip-backups}"
# 5 Mbps = 625 KB/s (rclone --bwlimit accepts K, M, G suffixes)
BW_LIMIT="${RCLONE_BW_LIMIT:-625K}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# ---------------------------------------------------------------------------
# Pre-flight bandwidth / connectivity check
# ---------------------------------------------------------------------------
preflight_check() {
    log "Pre-flight: verifying remote reachability..."

    if [ ! -f "$RCLONE_CONFIG" ]; then
        log "WARNING: rclone config not found at $RCLONE_CONFIG, skipping replication"
        exit 0
    fi

    # Connectivity + credential validation check via rclone ls
    if rclone ls \
        --config "$RCLONE_CONFIG" \
        --max-depth 1 \
        "${REMOTE_NAME}:${REMOTE_PATH}" >/dev/null 2>&1; then
        log "Remote ${REMOTE_NAME}:${REMOTE_PATH} is reachable"
    else
        log "WARNING: Cannot list remote ${REMOTE_NAME}:${REMOTE_PATH}. Aborting replication."
        exit 1
    fi

    # Optional independent bandwidth probe (non-fatal)
    if command -v speedtest-cli >/dev/null 2>&1; then
        log "Running speedtest-cli probe..."
        speedtest-cli --simple 2>/dev/null || log "WARNING: speedtest-cli probe failed"
    else
        log "speedtest-cli not available; skipping independent bandwidth measurement"
    fi

    log "Configured bwlimit: ${BW_LIMIT}"
}

preflight_check

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
