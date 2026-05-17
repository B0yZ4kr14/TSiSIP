#!/bin/sh
set -e

# TSiSIP OpenSIPS Health Check Script
# Layers: L1 (TCP 5060), L2 (MI get_statistics), L3 (SIP OPTIONS via sipsak/nc)

TIMEOUT=5
HOST=${OPENSIPS_HOST:-localhost}
MI_PORT=${OPENSIPS_MI_PORT:-8888}

# L1: TCP socket check
if ! nc -z -w "$TIMEOUT" "$HOST" 5060 2>/dev/null; then
    echo "FAIL: TCP 5060 not reachable"
    exit 1
fi

# L2: MI interface check
if ! wget -qO- "http://${HOST}:${MI_PORT}/mi/get_statistics" > /dev/null 2>&1; then
    echo "FAIL: MI interface not responding"
    exit 1
fi

# L3: SIP OPTIONS check (if sipsak available, else skip)
if command -v sipsak >/dev/null 2>&1; then
    if ! sipsak -s "sip:healthcheck@${HOST}" -vv > /dev/null 2>&1; then
        echo "FAIL: SIP OPTIONS not responding"
        exit 1
    fi
fi

echo "OK: OpenSIPS is healthy"
exit 0
