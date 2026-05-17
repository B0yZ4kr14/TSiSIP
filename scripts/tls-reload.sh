#!/bin/bash
# TSiSIP TLS Certificate Reload Script
# Reloads OpenSIPS TLS profile without downtime via MI command

set -euo pipefail

OPENSIPS_CONTAINER="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"
TIMEOUT=5

echo "[TLS-RELOAD] Reloading OpenSIPS TLS certificates..."

# Check if container is running
if ! docker ps | grep -q "$OPENSIPS_CONTAINER"; then
    echo "[TLS-RELOAD] ERROR: Container $OPENSIPS_CONTAINER not running"
    exit 1
fi

# Execute tls_reload via MI
start_time=$(date +%s.%N)
docker exec "$OPENSIPS_CONTAINER" opensipsctl fifo tls_reload
end_time=$(date +%s.%N)

# Calculate duration
duration=$(echo "$end_time - $start_time" | bc)
echo "[TLS-RELOAD] TLS reload completed in ${duration}s"

# Verify new certificate is active (check serial)
echo "[TLS-RELOAD] Verifying new certificate..."
docker exec "$OPENSIPS_CONTAINER" openssl x509 -in /run/secrets/server.crt -noout -serial -dates

echo "[TLS-RELOAD] Done. New connections will use the updated certificate."
echo "[TLS-RELOAD] Existing connections remain active with previous certificate."
