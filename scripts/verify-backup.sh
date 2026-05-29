#!/usr/bin/env bash
# TSiSIP Backup Restore Verification Script
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_DIR}/backups"
VERIFY_CONTAINER="tsisip-verify-backup"

# Defaults
BACKUP_FILE=""
ENCRYPTED=false
KEY_FILE="${PROJECT_DIR}/secrets/backup_encryption_key"

usage() {
    echo "Usage: $0 --backup <path> [--encrypted] [--key-file <path>]"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --backup) BACKUP_FILE="$2"; shift 2 ;;
        --encrypted) ENCRYPTED=true; shift ;;
        --key-file) KEY_FILE="$2"; shift 2 ;;
        *) usage ;;
    esac
done

[[ -z "$BACKUP_FILE" ]] && { echo "ERROR: --backup required"; usage; }
[[ ! -f "$BACKUP_FILE" ]] && { echo "ERROR: Backup not found: $BACKUP_FILE"; exit 1; }

echo "=== TSiSIP Backup Verification ==="
echo "Backup: $BACKUP_FILE"
echo "Time: $(date -Iseconds)"
echo ""

# Verify checksum
CHECKSUM_FILE="${BACKUP_FILE}.sha256"
if [[ -f "$CHECKSUM_FILE" ]]; then
    echo "Verifying checksum..."
    sha256sum -c "$CHECKSUM_FILE" >/dev/null 2>&1 && echo "  PASS" || { echo "  FAIL"; exit 1; }
else
    echo "  SKIP: no checksum file"
fi

# Handle encrypted backups
WORK_FILE="$BACKUP_FILE"
CLEANUP=false
if [[ "$ENCRYPTED" == true ]] || [[ "$BACKUP_FILE" == *.enc ]]; then
    [[ ! -f "$KEY_FILE" ]] && { echo "ERROR: Key file not found: $KEY_FILE"; exit 1; }
    echo "Decrypting..."
    WORK_FILE="${BACKUP_FILE%.enc}.decrypted.sql.gz"
    openssl enc -d -aes-256-cbc -pbkdf2 -in "$BACKUP_FILE" -out "$WORK_FILE" -pass "file:$KEY_FILE"
    CLEANUP=true
    echo "  Decrypted"
fi

# Verify compression
echo "Verifying compression..."
gunzip -t "$WORK_FILE" 2>/dev/null && echo "  PASS" || { echo "  FAIL"; [[ "$CLEANUP" == true ]] && rm -f "$WORK_FILE"; exit 1; }

# Start temp PostgreSQL
echo ""
echo "Starting temp PostgreSQL..."
docker rm -f "$VERIFY_CONTAINER" >/dev/null 2>&1 || true
PG_IMAGE=$(grep -A2 'postgres:' "${PROJECT_DIR}/docker-compose.yml" | grep 'image:' | head -1 | sed 's/.*image: *//' | tr -d ' "' || true)
PG_IMAGE=${PG_IMAGE:-postgres:15-alpine}

docker run -d --name "$VERIFY_CONTAINER" \
    -e POSTGRES_USER=opensips \
    -e POSTGRES_DB=opensips \
    -v "${WORK_FILE}:/tmp/backup.sql.gz:ro" \
    "$PG_IMAGE" >/dev/null

# Wait for ready
echo "Waiting for PostgreSQL..."
for i in {1..30}; do
    docker exec "$VERIFY_CONTAINER" pg_isready -U opensips -d opensips >/dev/null 2>&1 && { echo "  Ready"; break; }
    [[ $i -eq 30 ]] && { echo "  FAIL"; docker rm -f "$VERIFY_CONTAINER" >/dev/null 2>&1; [[ "$CLEANUP" == true ]] && rm -f "$WORK_FILE"; exit 1; }
    # Wait for async filesystem sync after tarball extraction
    sleep 1
done

# Restore
echo "Restoring..."
docker exec "$VERIFY_CONTAINER" sh -c 'gunzip -c /tmp/backup.sql.gz | psql -U opensips -d opensips >/dev/null 2>&1' && echo "  PASS" || { echo "  FAIL"; docker rm -f "$VERIFY_CONTAINER" >/dev/null 2>&1; [[ "$CLEANUP" == true ]] && rm -f "$WORK_FILE"; exit 1; }

# Validate tables
echo ""
echo "Validating schema..."
TABLES=(subscriber dispatcher version tenants pbx_backends header_routing_rules auth_audit_log sip_trunk_providers sip_trunk_did_mappings)
ALL_OK=true
for tbl in "${TABLES[@]}"; do
    COUNT=$(docker exec "$VERIFY_CONTAINER" psql -U opensips -d opensips -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_name='$tbl'" 2>/dev/null || echo 0)
    [[ "$COUNT" -eq 1 ]] && echo "  PASS: $tbl" || { echo "  FAIL: $tbl missing"; ALL_OK=false; }
done

[[ "$ALL_OK" != true ]] && { docker rm -f "$VERIFY_CONTAINER" >/dev/null 2>&1; [[ "$CLEANUP" == true ]] && rm -f "$WORK_FILE"; exit 1; }

# Row counts
echo ""
echo "Row counts:"
for q in "subscriber:SELECT COUNT(*) FROM subscriber" "dispatcher:SELECT COUNT(*) FROM dispatcher" "tenants:SELECT COUNT(*) FROM tenants"; do
    IFS=':' read -r name query <<< "$q"
    n=$(docker exec "$VERIFY_CONTAINER" psql -U opensips -d opensips -tAc "$query" 2>/dev/null || echo 0)
    echo "  $name: $n"
done

# Cleanup
echo ""
echo "Cleaning up..."
docker rm -f "$VERIFY_CONTAINER" >/dev/null 2>&1
[[ "$CLEANUP" == true ]] && rm -f "$WORK_FILE"

# Update metadata
META="${BACKUP_FILE}.meta.json"
[[ -f "$META" ]] && python3 -c "
import json
with open('$META') as f: d=json.load(f)
d['verify_status']='pass'; d['verify_timestamp']='$(date -Iseconds)'
with open('$META','w') as f: json.dump(d,f,indent=2)
" 2>/dev/null && echo "Metadata updated"

echo ""
echo "=== VERIFICATION PASSED ==="
