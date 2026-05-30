#!/bin/bash
# TSiSIP Backup Service Healthcheck
# Healthy if:
#   - A backup is currently running (lockfile exists)
#   - There's at least one encrypted backup newer than 24h (1440 min)
#   - It's a fresh deploy (no backups yet, but backup dir exists and is empty)

set -euo pipefail

# Backup in progress
if [ -f /tmp/backup.lock ]; then
    exit 0
fi

# Recent encrypted backup exists
if [ -d /backup/daily ] && [ "$(find /backup/daily -name '*.enc' -mmin -1440 | wc -l)" -gt 0 ]; then
    exit 0
fi

# Fresh deploy: backup dir exists but is empty (no backups yet)
if [ -d /backup/daily ] && [ -z "$(ls -A /backup/daily 2>/dev/null)" ]; then
    exit 0
fi

exit 1
