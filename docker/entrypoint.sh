#!/bin/sh
set -eu

if [ -f /run/secrets/db_password ]; then
    DB_PASSWORD="$(cat /run/secrets/db_password)"
    export DB_PASSWORD
fi
if [ -f /run/secrets/auth_secret ]; then
    AUTH_SECRET_32_CHARS="$(cat /run/secrets/auth_secret)"
    export AUTH_SECRET_32_CHARS
fi
if [ -f /run/secrets/topology_secret ]; then
    TOPOLOGY_SECRET="$(cat /run/secrets/topology_secret)"
    export TOPOLOGY_SECRET
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
