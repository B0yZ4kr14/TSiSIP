#!/bin/sh
set -eu

# Render pgbouncer.ini from template using environment variables
envsubst < /etc/pgbouncer/pgbouncer.ini.tpl > /etc/pgbouncer/pgbouncer.ini

# Start PgBouncer
exec pgbouncer /etc/pgbouncer/pgbouncer.ini
