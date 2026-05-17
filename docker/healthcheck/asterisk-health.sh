#!/bin/sh
set -e

# TSiSIP Asterisk Health Check Script
# Checks SIP socket on 5060 and AMI if available

HOST=${ASTERISK_HOST:-localhost}
SIP_PORT=${ASTERISK_SIP_PORT:-5060}
AMI_PORT=${ASTERISK_AMI_PORT:-5038}
TIMEOUT=5

# Check SIP socket
if ! nc -z -u -w "$TIMEOUT" "$HOST" "$SIP_PORT" 2>/dev/null; then
    echo "FAIL: Asterisk SIP port not reachable"
    exit 1
fi

# Check AMI if configured (optional)
if [ -n "$ASTERISK_AMI_USER" ] && [ -n "$ASTERISK_AMI_SECRET" ]; then
    if ! nc -z -w "$TIMEOUT" "$HOST" "$AMI_PORT" 2>/dev/null; then
        echo "FAIL: Asterisk AMI port not reachable"
        exit 1
    fi
fi

echo "OK: Asterisk is healthy"
exit 0
