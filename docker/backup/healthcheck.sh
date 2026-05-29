#!/bin/bash
# TSiSIP Backup Service Healthcheck
# Healthy if a backup is currently running (lockfile exists)
# OR if there's at least one encrypted backup newer than 24h (1440 min).

set -euo pipefail

if [ -f /tmp/backup.lock ]; then
    exit 0
fi

if [ "$(find /backup/daily -name '*.enc' -mmin -1440 | wc -l)" -gt 0 ]; then
    exit 0
fi

exit 1
