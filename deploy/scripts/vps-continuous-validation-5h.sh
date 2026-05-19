#!/usr/bin/env bash
set -euo pipefail

# Runs lightweight health/backup/WAL/metrics validations for 5 hours.
# Designed to be safe to stop/restart (idempotent, read-only checks).

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"

LOG_FILE="${LOG_FILE:-${PROJECT_ROOT}/reports/vps-continuous-validation.log}"
INTERVAL_SECONDS="${INTERVAL_SECONDS:-300}" # 5 minutes
TOTAL_SECONDS="${TOTAL_SECONDS:-18000}"     # 5 hours

ts() { date -Is; }
log() { printf '%s %s\n' "$(ts)" "$*" | tee -a "${LOG_FILE}" >/dev/null; }

end_at=$(( $(date +%s) + TOTAL_SECONDS ))
log "START vps-continuous-validation-5h total=${TOTAL_SECONDS}s interval=${INTERVAL_SECONDS}s"

while [[ "$(date +%s)" -lt "${end_at}" ]]; do
  log "CHECK docker-compose ps"
  docker compose -f "${COMPOSE_FILE}" ps --format 'table {{.Name}}\t{{.Status}}\t{{.Ports}}' | tee -a "${LOG_FILE}" >/dev/null || true

  log "CHECK pg_stat_archiver"
  docker compose -f "${COMPOSE_FILE}" exec -T postgres \
    psql -U opensips -d opensips -c "SELECT archived_count, failed_count, last_archived_wal FROM pg_stat_archiver;" \
    | tee -a "${LOG_FILE}" >/dev/null || true

  log "CHECK backup metrics"
  if curl -fsS http://127.0.0.1:9101/metrics >/dev/null 2>&1; then
    curl -fsS http://127.0.0.1:9101/metrics \
      | grep -E '^(backup_(rpo_lag_seconds|rto_last_seconds|validation_status|success_total|exporter_info))' \
      | tee -a "${LOG_FILE}" >/dev/null || true
  else
    log "WARN metrics endpoint not reachable on 127.0.0.1:9101"
  fi

  log "CHECK rpo-monitor (updates metrics source)"
  docker compose -f "${COMPOSE_FILE}" exec -T backup /usr/local/bin/rpo-monitor.sh \
    | tee -a "${LOG_FILE}" >/dev/null || true

  sleep "${INTERVAL_SECONDS}"
done

log "END vps-continuous-validation-5h"
