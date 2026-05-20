#!/bin/bash
# TSiSIP Secret Rotation Age Audit (SG4.1)
# Checks if any secret file is older than 90 days
# Exits 0 always (non-blocking warning)

set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SECRETS_DIR="$PROJECT_ROOT/secrets"
MAX_AGE_DAYS=90
WARNINGS=0

warn() { echo "[WARN] $*"; ((WARNINGS++)) || true; }
info() { echo "[INFO] $*"; }
pass() { echo "[PASS] $*"; }

info "=== Secret Age Audit (max age: ${MAX_AGE_DAYS} days) ==="

if [ ! -d "$SECRETS_DIR" ]; then
    info "secrets/ directory not found"
    exit 0
fi

NOW=$(date +%s)
MAX_AGE_SEC=$((MAX_AGE_DAYS * 86400))

for f in "$SECRETS_DIR"/*; do
    [ -f "$f" ] || continue
    BASENAME=$(basename "$f")
    MTIME=$(stat -c%Y "$f" 2>/dev/null || stat -f%m "$f" 2>/dev/null)
    AGE_SEC=$((NOW - MTIME))
    AGE_DAYS=$((AGE_SEC / 86400))
    
    if [ "$AGE_SEC" -gt "$MAX_AGE_SEC" ]; then
        warn "${BASENAME} is ${AGE_DAYS} days old (exceeds ${MAX_AGE_DAYS} days)"
    else
        pass "${BASENAME} is ${AGE_DAYS} days old (within limit)"
    fi
done

if [ $WARNINGS -gt 0 ]; then
    echo ""
    echo "Found $WARNINGS secret(s) older than ${MAX_AGE_DAYS} days. Rotate them."
    echo "See docs/security/008-secret-rotation-procedures.md for instructions."
fi

exit 0
