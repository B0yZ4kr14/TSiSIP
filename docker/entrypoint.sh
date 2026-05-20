#!/bin/sh
set -eu

# Helper: read a secret file, preserving internal newlines but stripping
# only the trailing newline (busybox tr -d '\n' incorrectly removes all 'n' chars)
read_secret() {
    if [ -f "$1" ]; then
        awk 'BEGIN{RS=""; ORS=""} {print}' "$1"
    fi
}

if [ -f /run/secrets/db_password ]; then
    DB_PASSWORD="$(read_secret /run/secrets/db_password)"
    export DB_PASSWORD
fi
if [ -f /run/secrets/auth_secret ]; then
    AUTH_SECRET_32_CHARS="$(read_secret /run/secrets/auth_secret)"
    export AUTH_SECRET_32_CHARS
fi
if [ -f /run/secrets/topology_secret ]; then
    TOPOLOGY_SECRET="$(read_secret /run/secrets/topology_secret)"
    export TOPOLOGY_SECRET
fi

# Preparar diretório TLS se secrets existirem
mkdir -p /etc/opensips/tls
for cert in ca.crt server.crt server.key crl.pem; do
    if [ -f "/run/secrets/${cert}" ]; then
        read_secret "/run/secrets/${cert}" > "/etc/opensips/tls/${cert}"
        chmod 644 "/etc/opensips/tls/${cert}"
    fi
done

# Bootstrap shared certificate volume if empty (Feature 014-A)
mkdir -p /certs/live
if [ ! -f /certs/live/server.crt ]; then
    for cert in server.crt server.key ca.crt; do
        if [ -f "/run/secrets/${cert}" ]; then
            read_secret "/run/secrets/${cert}" > "/certs/live/${cert}"
            chmod 644 "/certs/live/${cert}"
        fi
    done
fi

envsubst '
  $OPENSIPS_LISTEN_IP
  $HOST_PUBLIC_IP
  $DB_HOST
  $DB_NAME
  $DB_USER
  $DB_PASSWORD
  $AUTH_SECRET_32_CHARS
  $TOPOLOGY_SECRET
  $RTPENGINE_HOST
' < /etc/opensips/opensips.cfg.tpl > /etc/opensips/opensips.cfg

exec "$@"
