#!/usr/bin/env bash
# TSiSIP Database Backup Script
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_DIR}/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/tsisip_db_${TIMESTAMP}.sql.gz"
CHECKSUM_FILE="${BACKUP_FILE}.sha256"
META_FILE="${BACKUP_FILE}.meta.json"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)"

# Dump and compress
docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres \
    pg_dump -U opensips -d opensips --clean --if-exists | \
    gzip > "$BACKUP_FILE"

BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
BACKUP_SIZE_BYTES=$(stat -c%s "$BACKUP_FILE")
echo "Backup complete: $BACKUP_FILE ($BACKUP_SIZE)"

# Generate SHA-256 checksum
echo "Generating checksum..."
sha256sum "$BACKUP_FILE" > "$CHECKSUM_FILE"

# Verify checksum immediately
echo "Verifying checksum..."
if sha256sum -c "$CHECKSUM_FILE" >/dev/null 2>&1; then
    echo "Checksum verified successfully"
else
    echo "ERROR: Checksum verification failed"
    exit 1
fi

# Write metadata JSON
cat > "$META_FILE" <<EOF
{
  "timestamp": "$TIMESTAMP",
  "iso_timestamp": "$(date -Iseconds)",
  "file": "$(basename "$BACKUP_FILE")",
  "size_bytes": $BACKUP_SIZE_BYTES,
  "size_human": "$BACKUP_SIZE",
  "checksum": "$(cut -d' ' -f1 < "$CHECKSUM_FILE")",
  "checksum_file": "$(basename "$CHECKSUM_FILE")",
  "retention_days": $RETENTION_DAYS,
  "verify_status": "pending"
}
EOF
echo "Metadata written: $META_FILE"

# Clean old backups (backup files, checksums, and metadata)
find "$BACKUP_DIR" -name "tsisip_db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "tsisip_db_*.sql.gz.sha256" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "tsisip_db_*.sql.gz.meta.json" -mtime +$RETENTION_DAYS -delete
echo "Cleaned backups older than $RETENTION_DAYS days"

# Verify backup compression integrity
if gunzip -t "$BACKUP_FILE" 2>/dev/null; then
    echo "Backup compression verified successfully"
else
    echo "ERROR: Backup verification failed"
    exit 1
fi
