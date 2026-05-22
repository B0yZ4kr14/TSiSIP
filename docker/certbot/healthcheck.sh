#!/bin/sh
set -e

# TSiSIP Certbot Health Check Script
# Checks that the cron daemon is running and certificates exist.

CERT_DIR="/certs/live"
CERT_FILE="${CERT_DIR}/server.crt"

# Check cron daemon is running (crond or cron)
if ! pgrep -x "crond" >/dev/null 2>&1 && ! pgrep -x "cron" >/dev/null 2>&1; then
    echo "FAIL: Cron daemon not running"
    exit 1
fi

# Check certificate file exists
if [ ! -f "$CERT_FILE" ]; then
    echo "FAIL: Certificate not found at ${CERT_FILE}"
    exit 1
fi

# Validate certificate is not expired
if ! openssl x509 -in "$CERT_FILE" -noout -checkend 0 >/dev/null 2>&1; then
    echo "FAIL: Certificate at ${CERT_FILE} is expired"
    exit 1
fi

echo "OK: Certbot is healthy"
exit 0
