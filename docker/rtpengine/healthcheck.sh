#!/bin/sh
set -e

# TSiSIP RTPengine Health Check Script
# Checks UDP port 22222 (control) is reachable

# Default to 127.0.0.1 so it works inside host-network mode;
# override via RTPENGINE_HOST env when using Docker bridge networks.
HOST=${RTPENGINE_HOST:-127.0.0.1}
PORT=${RTPENGINE_NG_PORT:-22222}
TIMEOUT=5

# Check UDP port is reachable
if ! nc -z -u -w "$TIMEOUT" "$HOST" "$PORT" 2>/dev/null; then
    echo "FAIL: RTPengine control port not reachable"
    exit 1
fi

echo "OK: RTPengine is healthy"
exit 0
