#!/bin/bash
# TSiSIP Automated TLS Certificate Rotation Scheduler
# Runs expiry monitor and triggers rotation only when needed.
# Intended for cron-like execution (daily) or as a sidecar container.
#
# Usage: ./scripts/cert-auto-rotate.sh [--staging]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Auto-detect certificate path: VPS host path first, then container default
if [ -n "${CERT_PATH:-}" ]; then
    export CERT_PATH
elif [ -f "/opt/tsisip/secrets/server.crt" ]; then
    export CERT_PATH="/opt/tsisip/secrets/server.crt"
fi

# Auto-detect compose file: VPS production first, then default
if [ -n "${COMPOSE_FILE:-}" ]; then
    export COMPOSE_FILE
elif [ -f "$PROJECT_DIR/docker-compose.vps.yml" ]; then
    export COMPOSE_FILE="$PROJECT_DIR/docker-compose.vps.yml"
fi

WARN_DAYS="${WARN_DAYS:-30}"
STAGING=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --staging) STAGING=1; shift ;;
        -h|--help)
            echo "Usage: $0 [--staging]"
            echo ""
            echo "Environment variables:"
            echo "  CERT_PATH   Path to certificate to monitor"
            echo "  WARN_DAYS   Days before expiry to trigger rotation"
            exit 0
            ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

STAGING_ARG=""
if [ "$STAGING" -eq 1 ]; then
    STAGING_ARG="--staging"
fi

echo "[AUTO-ROTATE] Checking certificate expiry..."

if "$SCRIPT_DIR/cert-expiry-monitor.sh" >/dev/null 2>&1; then
    echo "[AUTO-ROTATE] Certificate is healthy. No action needed."
    exit 0
fi

echo "[AUTO-ROTATE] Certificate expires within ${WARN_DAYS} days. Triggering rotation..."

if "$SCRIPT_DIR/cert-rotate.sh" $STAGING_ARG; then
    echo "[AUTO-ROTATE] Rotation completed successfully."
    exit 0
else
    echo "[AUTO-ROTATE] ERROR: Rotation failed. Manual intervention required." >&2
    exit 1
fi
