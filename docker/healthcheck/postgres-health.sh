#!/bin/sh
set -e

# TSiSIP PostgreSQL Health Check Script
# Uses pg_isready to verify database connectivity

# Prefer socket-unix connection (no -h) for in-container checks.
# Fallback to TCP on localhost if socket is unavailable.

USER=${PGUSER:-opensips}
DB=${PGDATABASE:-opensips}
TIMEOUT=5

if pg_isready -U "$USER" -d "$DB" -t "$TIMEOUT" > /dev/null 2>&1; then
    echo "OK: PostgreSQL is healthy"
    exit 0
fi

if pg_isready -h localhost -U "$USER" -d "$DB" -t "$TIMEOUT" > /dev/null 2>&1; then
    echo "OK: PostgreSQL is healthy (localhost)"
    exit 0
fi

echo "FAIL: PostgreSQL not ready"
exit 1
