#!/bin/sh
# TSiSIP TLS Certificate Expiry Monitor (T3.2)
# Reads the server certificate and outputs days until expiry.
# Can be used standalone or integrated with Prometheus textfile collectors.

set -eu

CERT_PATH="${CERT_PATH:-/run/secrets/server.crt}"
WARN_DAYS="${WARN_DAYS:-30}"

if [ ! -f "$CERT_PATH" ]; then
    echo "ERROR: Certificate not found at $CERT_PATH" >&2
    exit 1
fi

# Parse expiry date
EXPIRY_DATE=$(openssl x509 -noout -enddate -in "$CERT_PATH" | cut -d= -f2)
EXPIRY_EPOCH=$(date -d "$EXPIRY_DATE" +%s 2>/dev/null || date -j -f "%b %d %H:%M:%S %Y %Z" "$EXPIRY_DATE" +%s 2>/dev/null)
NOW_EPOCH=$(date +%s)
DAYS_UNTIL=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))

# Output in human-readable format
echo "Certificate: $CERT_PATH"
echo "Expires on:  $EXPIRY_DATE"
echo "Days left:   $DAYS_UNTIL"

if [ "$DAYS_UNTIL" -lt "$WARN_DAYS" ]; then
    echo "STATUS: WARNING - Certificate expires in less than $WARN_DAYS days" >&2
    exit 1
fi

echo "STATUS: OK"
exit 0
