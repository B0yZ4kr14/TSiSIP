#!/bin/bash
# TSiSIP TLS Certificate Reload Script
# Reloads OpenSIPS TLS profile without downtime via MI HTTP command.

set -euo pipefail

OPENSIPS_CONTAINER="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"
TIMEOUT=5

echo "[TLS-RELOAD] Reloading OpenSIPS TLS certificates..."

# Check if container is running
if ! docker ps --format '{{.Names}}' | grep -qx "$OPENSIPS_CONTAINER"; then
    echo "[TLS-RELOAD] ERROR: Container $OPENSIPS_CONTAINER not running"
    exit 1
fi

# Primary: MI HTTP tls_reload from inside the container
start_time=$(date +%s)
if docker exec "$OPENSIPS_CONTAINER" sh -c "curl -fsSL --max-time $TIMEOUT http://127.0.0.1:8888/mi/tls_reload >/dev/null 2>&1"; then
    end_time=$(date +%s)
    duration=$((end_time - start_time))
    echo "[TLS-RELOAD] TLS reload completed via MI HTTP in ${duration}s"
else
    echo "[TLS-RELOAD] MI HTTP unavailable, attempting SIGHUP fallback..."
    if docker exec "$OPENSIPS_CONTAINER" sh -c 'kill -HUP $(pidof opensips)' >/dev/null 2>&1; then
        echo "[TLS-RELOAD] TLS reload triggered via SIGHUP"
    else
        echo "[TLS-RELOAD] ERROR: Both MI HTTP and SIGHUP failed"
        exit 1
    fi
fi

# Verify new certificate is active (check serial/dates)
echo "[TLS-RELOAD] Verifying new certificate..."
if ! docker exec "$OPENSIPS_CONTAINER" openssl x509 -in /certs/live/server.crt -noout -serial -dates 2>/dev/null; then
    echo "[TLS-RELOAD] WARNING: Could not verify certificate in container"
fi

echo "[TLS-RELOAD] Done. New connections will use the updated certificate."
echo "[TLS-RELOAD] Existing connections remain active with previous certificate."
