#!/bin/sh
set -e

CERT_DIR="/certs/live"
TMP_CERT="${CERT_DIR}/server.crt.new"
TMP_KEY="${CERT_DIR}/server.key.new"
TMP_CHAIN="${CERT_DIR}/chain.pem.new"

# Ensure cert dir exists
mkdir -p "$CERT_DIR"

# Copy renewed certificates
cp "$RENEWED_LINEAGE/fullchain.pem" "$TMP_CERT"
cp "$RENEWED_LINEAGE/privkey.pem"   "$TMP_KEY"
cp "$RENEWED_LINEAGE/chain.pem"     "$TMP_CHAIN"

# Validate certificate and key before swapping
openssl x509 -in "$TMP_CERT" -noout -checkend 86400 >/dev/null
openssl rsa -in "$TMP_KEY" -check -noout >/dev/null

# Atomic swap
mv -f "$TMP_CERT"  "${CERT_DIR}/server.crt"
mv -f "$TMP_KEY"   "${CERT_DIR}/server.key"
mv -f "$TMP_CHAIN" "${CERT_DIR}/chain.pem"

# Trigger OpenSIPS reload via MI HTTP
OPENSIPS_MI_URL="${OPENSIPS_MI_URL:-http://opensips:8888/mi}"

if curl -fsSL -X POST "${OPENSIPS_MI_URL}/tls_reload" >/dev/null 2>&1; then
    echo "[CERTBOT] Deployed new certificate and triggered tls_reload via MI HTTP"
    exit 0
fi

# Fallback: attempt SIGHUP if opensips PID is reachable
echo "[CERTBOT] MI HTTP unavailable, attempting SIGHUP fallback" >&2
if [ -f /var/run/opensips.pid ]; then
    kill -HUP "$(cat /var/run/opensips.pid)" 2>/dev/null && \
        echo "[CERTBOT] Triggered tls_reload via SIGHUP" && exit 0
fi

# Last resort: try docker kill if socket is mounted
if [ -S /var/run/docker.sock ] && [ -x "$(command -v docker)" ]; then
    docker kill --signal=HUP "$(docker ps -q -f name=opensips)" 2>/dev/null && \
        echo "[CERTBOT] Triggered tls_reload via docker kill SIGHUP" && exit 0
fi

echo "[CERTBOT] WARNING: Could not trigger tls_reload via MI HTTP or SIGHUP" >&2
exit 1
