#!/bin/bash
# auto-healer.sh — Cron wrapper for TSiSIP auto-healer (Feature 036)
# Runs every minute via cron inside the OCP container

LOG_FILE="/var/log/tsisip/auto-healer.log"
LOCK_FILE="/tmp/auto-healer.lock"

# Prevent overlapping runs
if [ -f "$LOCK_FILE" ]; then
    PID=$(cat "$LOCK_FILE" 2>/dev/null)
    if kill -0 "$PID" 2>/dev/null; then
        echo "$(date -Iseconds) Auto-healer already running (PID $PID)" >> "$LOG_FILE"
        exit 0
    fi
fi

echo $$ > "$LOCK_FILE"
/usr/local/bin/php /var/www/html/cli/auto-healer.php >> "$LOG_FILE" 2>&1
EXIT_CODE=$?
rm -f "$LOCK_FILE"

if [ $EXIT_CODE -ne 0 ]; then
    echo "$(date -Iseconds) Auto-healer exited with code $EXIT_CODE" >> "$LOG_FILE"
fi
