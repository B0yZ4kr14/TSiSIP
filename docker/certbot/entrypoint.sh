#!/bin/sh
set -e

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

# Start cron for scheduled renewals
echo "[CERTBOT] Starting cron daemon for scheduled renewals..."
if command -v crond >/dev/null 2>&1; then
    exec crond -f
elif command -v cron >/dev/null 2>&1; then
    exec cron -f
else
    echo "[CERTBOT] ERROR: No cron daemon found"
    exit 1
fi
