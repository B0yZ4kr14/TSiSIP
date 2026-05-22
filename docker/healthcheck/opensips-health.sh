#!/bin/sh
set -e

# TSiSIP OpenSIPS Health Check Script
# Checks: process existence + TCP 5060 listening

TIMEOUT=5
HOST=${OPENSIPS_HOST:-127.0.0.1}

# L1: process check
if ! pgrep -x opensips > /dev/null 2>&1; then
    echo "FAIL: OpenSIPS process not running"
    exit 1
fi

# L2: TCP socket check (use bash /dev/tcp if available, else nc)
if [ -e /bin/bash ] || [ -e /usr/bin/bash ]; then
    if ! bash -c "timeout ${TIMEOUT} bash -c 'echo > /dev/tcp/${HOST}/5060'" 2>/dev/null; then
        echo "FAIL: TCP 5060 not reachable"
        exit 1
    fi
elif command -v nc >/dev/null 2>&1; then
    if ! nc -z -w "$TIMEOUT" "$HOST" 5060 2>/dev/null; then
        echo "FAIL: TCP 5060 not reachable"
        exit 1
    fi
else
    # Fallback: parse /proc/net/tcp for port 0x13C4 (5060)
    if ! awk '$2 ~ /:13C4$/ && $4 == "0A" {found=1} END {exit !found}' /proc/net/tcp 2>/dev/null; then
        echo "FAIL: TCP 5060 not listening"
        exit 1
    fi
fi

echo "OK: OpenSIPS is healthy"
exit 0
