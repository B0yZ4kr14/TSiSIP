#!/bin/bash
set -euo pipefail

# TSiSIP WAL Archive Script
# Called by PostgreSQL archive_command
# Compresses and encrypts WAL segments

WAL_SRC="${1:-}"
WAL_DST="${2:-}"
WAL_DIR="${WAL_DIR:-/backup/wal}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"

[ -z "$WAL_SRC" ] || [ -z "$WAL_DST" ] && exit 1

# Create destination directory
WAL_SUBDIR="$(dirname "$WAL_DST")"
mkdir -p "${WAL_DIR}/${WAL_SUBDIR}"

# Compress WAL segment
gzip -c "$WAL_SRC" > "${WAL_DIR}/${WAL_DST}.gz"

# Encrypt if key available
if [ -f "$ENCRYPTION_KEY_FILE" ] && [ -s "$ENCRYPTION_KEY_FILE" ]; then
    /usr/local/bin/encrypt.sh encrypt "${WAL_DIR}/${WAL_DST}.gz" "${WAL_DIR}/${WAL_DST}.gz.enc"
    rm -f "${WAL_DIR}/${WAL_DST}.gz"
fi

exit 0
