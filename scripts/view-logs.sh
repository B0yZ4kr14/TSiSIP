#!/usr/bin/env bash
# TSiSIP Log Viewer
set -euo pipefail

SERVICE="${1:-all}"
TAIL="${2:-100}"

echo "=== TSiSIP Logs ($SERVICE, last $TAIL lines) ==="

if [ "$SERVICE" = "all" ]; then
    docker compose logs --tail="$TAIL" -f
else
    docker compose logs --tail="$TAIL" -f "$SERVICE"
fi
