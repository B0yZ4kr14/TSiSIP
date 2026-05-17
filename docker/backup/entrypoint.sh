#!/bin/bash
set -euo pipefail

# TSiSIP Backup Container Entrypoint
# Sets up cron jobs and starts cron daemon

# Render rclone config from template
if [ -f /etc/rclone/rclone.conf.tpl ]; then
    envsubst < /etc/rclone/rclone.conf.tpl > /etc/rclone/rclone.conf
fi

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

# Execute command
exec "$@"
