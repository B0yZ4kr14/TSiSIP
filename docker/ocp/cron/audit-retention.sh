#!/bin/bash
set -euo pipefail

# TSiSIP OCP — Audit Log Retention Purge
# Connects to PostgreSQL and runs the canonical retention purge function.
# Intended to be invoked from cron inside the OCP container.

RETENTION_DAYS="${OCP_AUDIT_RETENTION_DAYS:-90}"
DB_HOST="${DB_HOST:-postgres}"
DB_NAME="${DB_NAME:-opensips}"
DB_USER="${DB_USER:-opensips}"

DB_PASS=""
if [ -f /tmp/db_password ]; then
    DB_PASS="$(cat /tmp/db_password)"
elif [ -f /run/secrets/db_password ]; then
    DB_PASS="$(cat /run/secrets/db_password)"
else
    DB_PASS="${DB_PASSWORD:-}"
fi

export PGPASSWORD="${DB_PASS}"

LOGFILE="/var/log/tsisip/audit-retention.log"
mkdir -p "$(dirname "$LOGFILE")"

echo "[$(date -Iseconds)] Starting audit retention purge (retention=${RETENTION_DAYS} days)" >> "$LOGFILE"

RESULT=$(psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" \
    -t -A -c "SELECT ocp_audit_log_retention_purge(${RETENTION_DAYS});" 2>>"$LOGFILE") || {
    echo "[$(date -Iseconds)] ERROR: psql command failed" >> "$LOGFILE"
    exit 1
}

# Trim whitespace
RESULT=$(echo "$RESULT" | tr -d '[:space:]')

if [ -n "$RESULT" ] && [ "$RESULT" -eq "$RESULT" ] 2>/dev/null; then
    echo "[$(date -Iseconds)] Purged ${RESULT} audit log rows" >> "$LOGFILE"
else
    echo "[$(date -Iseconds)] ERROR: unexpected result from purge function: '${RESULT}'" >> "$LOGFILE"
    exit 1
fi
