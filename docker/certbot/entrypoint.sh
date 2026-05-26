#!/bin/sh
set -eu

CERT_DIR="/etc/letsencrypt"
LIVE_DIR="/certs/live"
DEPLOY_HOOK="/usr/local/bin/deploy-hook.sh"

# Ensure directories exist
mkdir -p "$LIVE_DIR"

# If no certificate exists for TLS_DOMAIN, perform initial issuance
if [ ! -d "$CERT_DIR/live/${TLS_DOMAIN}" ]; then
    echo "[CERTBOT] No certificate found for ${TLS_DOMAIN}. Performing initial issuance..."

    STAGING_ARG=""
    if [ "${CERTBOT_STAGING:-0}" = "1" ]; then
        STAGING_ARG="--staging"
        echo "[CERTBOT] Using ACME staging environment"
    fi

    # Default to standalone if no auth method specified
    AUTH_METHOD="${CERTBOT_AUTH_METHOD:-standalone}"

    case "$AUTH_METHOD" in
        standalone)
            /usr/local/bin/certbot certonly --standalone \
                -d "$TLS_DOMAIN" \
                --email "$ACME_EMAIL" \
                --agree-tos --non-interactive \
                $STAGING_ARG \
                --deploy-hook "$DEPLOY_HOOK"
            ;;
        webroot)
            WEBROOT_PATH="${CERTBOT_WEBROOT_PATH:-/var/www/certbot}"
            /usr/local/bin/certbot certonly --webroot -w "$WEBROOT_PATH" \
                -d "$TLS_DOMAIN" \
                --email "$ACME_EMAIL" \
                --agree-tos --non-interactive \
                $STAGING_ARG \
                --deploy-hook "$DEPLOY_HOOK"
            ;;
        *)
            echo "[CERTBOT] ERROR: Unknown auth method: $AUTH_METHOD"
            exit 1
            ;;
    esac

    echo "[CERTBOT] Initial issuance complete."
else
    echo "[CERTBOT] Certificate for ${TLS_DOMAIN} already exists."
fi

# Start renewal loop (cron daemons require setpgid which fails in hardened containers)
echo "[CERTBOT] Starting renewal loop (checking every 12 hours)..."
RENEWAL_INTERVAL="${CERTBOT_RENEWAL_INTERVAL:-43200}"  # 12 hours in seconds

while true; do
    echo "[CERTBOT] Running certbot renew check at $(date -Iseconds)"
    /usr/local/bin/certbot renew --non-interactive --deploy-hook "$DEPLOY_HOOK" || true
    echo "[CERTBOT] Next check in ${RENEWAL_INTERVAL}s"
    sleep "$RENEWAL_INTERVAL"
done
