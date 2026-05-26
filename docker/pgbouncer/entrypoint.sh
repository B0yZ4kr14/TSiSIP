#!/bin/sh
set -eu

# Render pgbouncer.ini from template using environment variables
envsubst < /etc/pgbouncer/pgbouncer.ini.tpl > /etc/pgbouncer/pgbouncer.ini

# Validate config
if ! pgbouncer /etc/pgbouncer/pgbouncer.ini --check; then
    echo "ERROR: PgBouncer config validation failed"
    exit 1
fi

# Start PgBouncer
exec pgbouncer /etc/pgbouncer/pgbouncer.ini
