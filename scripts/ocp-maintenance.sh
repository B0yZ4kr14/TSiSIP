#!/usr/bin/env bash
# TSiSIP OCP Maintenance Script
# Run daily via cron for cleanup and optimization.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_FILE="${PROJECT_DIR}/logs/ocp-maintenance-$(date +%Y%m%d).log"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

log "=== TSiSIP OCP Maintenance Started ==="

# Clean old audit logs (> 90 days)
log "Cleaning old audit logs..."
docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres psql -U opensips -d opensips -c "
    DELETE FROM ocp_audit_log WHERE event_time < NOW() - INTERVAL '90 days';
" 2>/dev/null || log "WARN: Could not clean audit logs"

# Clean expired sessions
log "Cleaning expired PHP sessions..."
docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T ocp find /var/lib/php/sessions -type f -mtime +7 -delete 2>/dev/null || log "WARN: Could not clean sessions"

# Vacuum database
log "Vacuuming database..."
docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres psql -U opensips -d opensips -c "VACUUM ANALYZE;" 2>/dev/null || log "WARN: Could not vacuum"

# Check disk space
log "Checking disk space..."
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 80 ]; then
    log "WARNING: Disk usage is ${DISK_USAGE}%"
fi

# Health check
log "Running health check..."
if curl -fsSL http://localhost/health.php >/dev/null 2>&1; then
    log "Health check: OK"
else
    log "ERROR: Health check failed"
fi

# Rotate logs
log "Rotating logs..."
find "${PROJECT_DIR}/logs" -name "ocp-maintenance-*.log" -mtime +30 -delete 2>/dev/null || true

log "=== Maintenance Completed ==="
