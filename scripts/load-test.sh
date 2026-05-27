#!/usr/bin/env bash
# TSiSIP Load Test
set -euo pipefail

URL="${1:-http://localhost}"
CONCURRENT="${2:-10}"
REQUESTS="${3:-100}"

echo "=== TSiSIP Load Test ==="
echo "URL: $URL"
echo "Concurrent: $CONCURRENT"
echo "Requests: $REQUESTS"

# Health endpoint
echo "Testing health endpoint..."
ab -n $REQUESTS -c $CONCURRENT "$URL/health.php" 2>/dev/null || echo "ab not installed, using curl fallback"

# Fallback with curl
if ! command -v ab &> /dev/null; then
    echo "Using curl fallback..."
    for i in $(seq 1 $REQUESTS); do
        curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" "$URL/health.php" &
        if ((i % CONCURRENT == 0)); then wait; fi
    done
    wait
fi

echo "=== Load Test Complete ==="
