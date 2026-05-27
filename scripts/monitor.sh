#!/usr/bin/env bash
# TSiSIP System Monitor
# Run every 5 minutes via cron for proactive alerting.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ALERT_LOG="${PROJECT_DIR}/logs/alerts.log"
HEALTH_URL="${TSISIP_HEALTH_URL:-http://localhost/health.php}"

mkdir -p "$(dirname "$ALERT_LOG")"

alert() {
    local level="$1"
    local msg="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $msg" | tee -a "$ALERT_LOG"
    # Future: send email, webhook, etc.
}

# Check health endpoint
if ! curl -fsSL "$HEALTH_URL" >/dev/null 2>&1; then
    alert "CRITICAL" "Health check failed: $HEALTH_URL"
    exit 1
fi

# Parse health JSON
HEALTH=$(curl -fsSL "$HEALTH_URL" 2>/dev/null || echo '{"status":"unknown"}')
STATUS=$(echo "$HEALTH" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)

if [ "$STATUS" != "healthy" ]; then
    alert "WARNING" "System status is: $STATUS"
fi

# Check individual components
echo "$HEALTH" | grep -q '"status":"ok".*"database"' || alert "WARNING" "Database check failed"
echo "$HEALTH" | grep -q '"status":"ok".*"opensips"' || alert "WARNING" "OpenSIPS check failed"

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 90 ]; then
    alert "CRITICAL" "Disk usage is ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -gt 75 ]; then
    alert "WARNING" "Disk usage is ${DISK_USAGE}%"
fi

# Check memory
MEM_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ "$MEM_USAGE" -gt 90 ]; then
    alert "WARNING" "Memory usage is ${MEM_USAGE}%"
fi

# Check container status
for service in opensips rtpengine postgres ocp; do
    if ! docker compose -f "${PROJECT_DIR}/docker-compose.yml" ps "$service" | grep -q "Up"; then
        alert "CRITICAL" "Container $service is not running"
    fi
done

echo "Monitor check complete at $(date)"
