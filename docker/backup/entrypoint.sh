#!/bin/bash
set -euo pipefail

# TSiSIP Backup Container Entrypoint
# Sets up cron jobs and starts cron daemon

# Load S3 credentials from Docker secrets if available
# (B17 Remediation — replaces plain env vars for RCLONE_S3_ACCESS_KEY / RCLONE_S3_SECRET_KEY)
if [ -f /run/secrets/rclone_s3_access_key ] && [ -r /run/secrets/rclone_s3_access_key ]; then
    export RCLONE_S3_ACCESS_KEY="$(cat /run/secrets/rclone_s3_access_key)"
fi
if [ -f /run/secrets/rclone_s3_secret_key ] && [ -r /run/secrets/rclone_s3_secret_key ]; then
    export RCLONE_S3_SECRET_KEY="$(cat /run/secrets/rclone_s3_secret_key)"
fi

# Render rclone config from template
if [ -f /etc/rclone/rclone.conf.tpl ]; then
    envsubst < /etc/rclone/rclone.conf.tpl > /etc/rclone/rclone.conf
fi

# Ensure volume directories exist and that Postgres can write WAL archives.
# The shared /backup volume is commonly root-owned on first boot.
mkdir -p "${BACKUP_DIR:-/backup/daily}" "${WAL_DIR:-/backup/wal}" "${METRICS_DIR:-/backup/metrics}" /backup/validate /tmp/backup
POSTGRES_UID="${POSTGRES_UID:-999}"
POSTGRES_GID="${POSTGRES_GID:-999}"
chown -R "${POSTGRES_UID}:${POSTGRES_GID}" "${WAL_DIR:-/backup/wal}" 2>/dev/null || true
chmod 750 "${WAL_DIR:-/backup/wal}" 2>/dev/null || true
# Ensure tsisip-backup user can write to its directories
chown -R tsisip-backup:tsisip-backup "${BACKUP_DIR:-/backup/daily}" "${METRICS_DIR:-/backup/metrics}" /backup/validate /tmp/backup 2>/dev/null || true

# Setup cron jobs
CRON_FILE="/tmp/crontab"
{
    echo "# TSiSIP Backup Jobs"
    echo "BACKUP_DIR=${BACKUP_DIR}"
    echo "WAL_DIR=${WAL_DIR}"
    echo "PGHOST=${PGHOST}"
    echo "PGPORT=${PGPORT}"
    echo "PGUSER=${PGUSER}"
    echo "PGDATABASE=${PGDATABASE}"
    echo "ENCRYPTION_KEY_FILE=${ENCRYPTION_KEY_FILE}"
    echo "PGPASSWORD_FILE=${PGPASSWORD_FILE:-/run/secrets/db_password}"
    echo "METRICS_DIR=${METRICS_DIR}"
    echo ""
    echo "# Daily backup at 02:00 UTC"
    echo "${BACKUP_SCHEDULE} /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1"
    echo ""
    echo "# Retention purge at 03:00 UTC"
    echo "${PURGE_SCHEDULE} /usr/local/bin/purge.sh >> /var/log/purge.log 2>&1"
    echo ""
    echo "# Validation at 04:00 UTC"
    echo "${VALIDATE_SCHEDULE} /usr/local/bin/validate.sh >> /var/log/validate.log 2>&1"
    echo ""
    echo "# Hourly replication"
    echo "${REPLICATE_SCHEDULE} /usr/local/bin/replicate.sh >> /var/log/replicate.log 2>&1"
    echo ""
    echo "# RPO monitor every 5 minutes"
    echo "${RPO_SCHEDULE:-*/5 * * * *} /usr/local/bin/rpo-monitor.sh >> /var/log/rpo-monitor.log 2>&1"
    echo ""
    echo "# Quota check every 10 minutes"
    echo "${QUOTA_SCHEDULE:-*/10 * * * *} /usr/local/bin/quota-check.sh >> /var/log/quota-check.log 2>&1"
} > "$CRON_FILE"

# Install crontab
crontab "$CRON_FILE"
rm -f "$CRON_FILE"

# Create log directory
mkdir -p /var/log

echo "TSiSIP Backup container started"
echo "Backup schedule: $BACKUP_SCHEDULE"
echo "Purge schedule: $PURGE_SCHEDULE"
echo "Validate schedule: $VALIDATE_SCHEDULE"
echo "Replicate schedule: $REPLICATE_SCHEDULE"
echo "RPO monitor schedule: ${RPO_SCHEDULE:-*/5 * * * *}"
echo "Quota check schedule: ${QUOTA_SCHEDULE:-*/10 * * * *}"

# Start metrics exporter in background as non-root user
if [ -x /usr/local/bin/metrics-exporter.sh ]; then
    echo "Starting metrics exporter on ${METRICS_ADDR:-0.0.0.0}:${METRICS_PORT:-9101}"
    gosu tsisip-backup /usr/local/bin/metrics-exporter.sh &
fi

# Execute command
exec "$@"
