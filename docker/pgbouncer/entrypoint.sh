#!/bin/sh
set -eu

# Generate userlist.txt from secret if available
USERLIST="/etc/pgbouncer/userlist.txt"
DB_PASSWORD="${DB_PASSWORD:-}"

# Prefer secret mount, fallback to env var
if [ -f /run/secrets/db_password ]; then
    DB_PASSWORD=$(cat /run/secrets/db_password | tr -d '\r\n')
elif [ -f /tmp/db_password ]; then
    DB_PASSWORD=$(cat /tmp/db_password | tr -d '\r\n')
fi

if [ -n "$DB_PASSWORD" ]; then
    printf '"opensips" "%s"\n' "$DB_PASSWORD" > "$USERLIST"
else
    # Empty password — still create userlist for trust mode compatibility
    printf '"opensips" ""\n' > "$USERLIST"
fi

# Render pgbouncer.ini from template using environment variables
envsubst < /etc/pgbouncer/pgbouncer.ini.tpl > /etc/pgbouncer/pgbouncer.ini

# Start PgBouncer
exec pgbouncer /etc/pgbouncer/pgbouncer.ini
