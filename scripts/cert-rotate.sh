#!/bin/bash
# TSiSIP Automated TLS Certificate Rotation — Operator Script
# Orchestrates one-shot certificate rotation via ACME (Certbot) or Tailscale.
# Usage: ./scripts/cert-rotate.sh [--staging]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_DIR/docker-compose.yml}"

STAGING=0
while [[ $# -gt 0 ]]; do
    case "$1" in
        --staging) STAGING=1; shift ;;
        -h|--help)
            echo "Usage: $0 [--staging]"
            echo ""
            echo "Options:"
            echo "  --staging   Use ACME staging environment for testing"
            exit 0
            ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Load environment defaults from .env if present
if [ -f "$PROJECT_DIR/.env" ]; then
    set -a
    # shellcheck source=/dev/null
    source "$PROJECT_DIR/.env"
    set +a
fi

TLS_DOMAIN="${TLS_DOMAIN:-sip.example.com}"
ACME_EMAIL="${ACME_EMAIL:-admin@example.com}"
CERTBOT_STAGING="${CERTBOT_STAGING:-0}"
TAILSCALE_CERT_ENABLED="${TAILSCALE_CERT_ENABLED:-0}"
TS_HOSTNAME="${TS_HOSTNAME:-tsisip}"
OPENSIPS_MI_URL="${OPENSIPS_MI_URL:-http://localhost:8888/mi}"
OPENSIPS_CONTAINER="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"

if [ "$STAGING" -eq 1 ]; then
    echo "[CERT-ROTATE] STAGING mode enabled"
    CERTBOT_STAGING=1
fi

echo "[CERT-ROTATE] Starting certificate rotation for ${TLS_DOMAIN}..."

# Determine backend
if [ "$TAILSCALE_CERT_ENABLED" = "1" ]; then
    echo "[CERT-ROTATE] Backend: Tailscale cert"
    docker compose -f "$COMPOSE_FILE" run --rm tailscale-cert
else
    echo "[CERT-ROTATE] Backend: ACME (Certbot)"

    STAGING_ARG=""
    if [ "$CERTBOT_STAGING" = "1" ]; then
        STAGING_ARG="--staging"
    fi

    # Check if certificate already exists
    if docker compose -f "$COMPOSE_FILE" run --rm certbot /usr/local/bin/certbot certificates 2>/dev/null | grep -q "$TLS_DOMAIN"; then
        echo "[CERT-ROTATE] Existing certificate found. Forcing renewal..."
        docker compose -f "$COMPOSE_FILE" run --rm certbot /usr/local/bin/certbot renew \
            --force-renewal $STAGING_ARG --deploy-hook /usr/local/bin/deploy-hook.sh
    else
        echo "[CERT-ROTATE] Performing initial certificate issuance..."
        # Default to standalone; operator must map port 80 if using standalone
        docker compose -f "$COMPOSE_FILE" run --rm certbot /usr/local/bin/certbot certonly --standalone \
            -d "$TLS_DOMAIN" --email "$ACME_EMAIL" --agree-tos --non-interactive \
            $STAGING_ARG --deploy-hook /usr/local/bin/deploy-hook.sh
    fi
fi

# Validate the certificate
echo "[CERT-ROTATE] Validating certificate..."
VALIDATION_CONTAINER="certbot"
if [ "$TAILSCALE_CERT_ENABLED" = "1" ]; then
    VALIDATION_CONTAINER="tailscale-cert"
fi
if ! docker compose -f "$COMPOSE_FILE" run --rm "$VALIDATION_CONTAINER" openssl x509 \
    -in /certs/live/server.crt -noout -checkend 86400 >/dev/null 2>&1; then
    echo "[CERT-ROTATE] ERROR: Certificate validation failed"
    exit 1
fi

# Trigger OpenSIPS TLS reload
echo "[CERT-ROTATE] Triggering OpenSIPS TLS reload..."
if "${SCRIPT_DIR}/tls-reload.sh" 2>/dev/null; then
    echo "[CERT-ROTATE] tls_reload triggered successfully"
else
    echo "[CERT-ROTATE] WARNING: Could not trigger tls_reload"
fi

echo "[CERT-ROTATE] Certificate rotation completed successfully."
