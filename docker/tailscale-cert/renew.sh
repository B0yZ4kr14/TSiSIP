#!/bin/sh
set -e

CERT_DIR="/certs/live"
TS_HOSTNAME="${TS_HOSTNAME:-tsisip}"
OPENSIPS_MI_URL="${OPENSIPS_MI_URL:-http://opensips:8888/mi}"

mkdir -p "$CERT_DIR"

# Run tailscale cert to obtain/renew certificate for the machine
# tailscale cert is idempotent and only reissues when within 14 days of expiry
tailscale cert --cert-file="${CERT_DIR}/${TS_HOSTNAME}.crt" --key-file="${CERT_DIR}/${TS_HOSTNAME}.key" "${TS_HOSTNAME}"

# Validate the renewed certificate
openssl x509 -in "${CERT_DIR}/${TS_HOSTNAME}.crt" -noout -checkend 86400 >/dev/null

# Atomically copy to canonical filenames expected by OpenSIPS/RTPengine
cp -f "${CERT_DIR}/${TS_HOSTNAME}.crt" "${CERT_DIR}/server.crt.new"
cp -f "${CERT_DIR}/${TS_HOSTNAME}.key" "${CERT_DIR}/server.key.new"
# Tailscale cert output is the full chain; reuse it for chain.pem
cp -f "${CERT_DIR}/${TS_HOSTNAME}.crt" "${CERT_DIR}/chain.pem.new"

mv -f "${CERT_DIR}/server.crt.new" "${CERT_DIR}/server.crt"
mv -f "${CERT_DIR}/server.key.new" "${CERT_DIR}/server.key"
mv -f "${CERT_DIR}/chain.pem.new" "${CERT_DIR}/chain.pem"

# Trigger OpenSIPS reload via MI HTTP
if curl -fsSL -X POST "${OPENSIPS_MI_URL}/tls_reload" >/dev/null 2>&1; then
    echo "[TAILSCALE-CERT] Renewed certificate and triggered tls_reload via MI HTTP"
    exit 0
fi

# Fallback: attempt SIGHUP if opensips PID is reachable
echo "[TAILSCALE-CERT] MI HTTP unavailable, attempting SIGHUP fallback" >&2
if [ -f /var/run/opensips.pid ]; then
    kill -HUP "$(cat /var/run/opensips.pid)" 2>/dev/null && \
        echo "[TAILSCALE-CERT] Triggered tls_reload via SIGHUP" && exit 0
fi

# Last resort: try docker kill if socket is mounted
if [ -S /var/run/docker.sock ] && [ -x "$(command -v docker)" ]; then
    docker kill --signal=HUP "$(docker ps -q -f name=opensips)" 2>/dev/null && \
        echo "[TAILSCALE-CERT] Triggered tls_reload via docker kill SIGHUP" && exit 0
fi

echo "[TAILSCALE-CERT] WARNING: Could not trigger tls_reload via MI HTTP or SIGHUP" >&2
exit 1
