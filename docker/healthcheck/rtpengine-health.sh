#!/bin/sh
set -e

# TSiSIP RTPengine Health Check Script
# Checks UDP port 22222 (control) and verifies ng protocol response

HOST=${RTPENGINE_HOST:-rtpengine}
PORT=${RTPENGINE_NG_PORT:-22222}
TIMEOUT=5

# Check UDP port is reachable
if ! nc -z -u -w "$TIMEOUT" "$HOST" "$PORT" 2>/dev/null; then
    echo "FAIL: RTPengine control port not reachable"
    exit 1
fi

# Try to get statistics via ng protocol (echo request)
# Send simple ping and expect pong
if command -v rtpengine-ctl >/dev/null 2>&1; then
    if ! rtpengine-ctl -h "$HOST" -p "$PORT" list > /dev/null 2>&1; then
        echo "FAIL: RTPengine ng protocol not responding"
        exit 1
    fi
fi

echo "OK: RTPengine is healthy"
exit 0
