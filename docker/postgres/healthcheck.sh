#!/bin/sh
set -e

# TSiSIP PostgreSQL Health Check Script
# Uses pg_isready to verify database connectivity

HOST=${PGHOST:-postgres}
PORT=${PGPORT:-5432}
USER=${PGUSER:-opensips}
DB=${PGDATABASE:-opensips}
TIMEOUT=5

if ! pg_isready -h "$HOST" -p "$PORT" -U "$USER" -d "$DB" -t "$TIMEOUT" > /dev/null 2>&1; then
    echo "FAIL: PostgreSQL not ready"
    exit 1
fi

echo "OK: PostgreSQL is healthy"
exit 0
