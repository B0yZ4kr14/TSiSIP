#!/bin/bash
set -euo pipefail

# TSiSIP Backup Metrics Exporter for Prometheus
# Serves RPO, RTO, backup status, and quota metrics in Prometheus text format

METRICS_DIR="${METRICS_DIR:-/backup/metrics}"
LISTEN_PORT="${METRICS_PORT:-9101}"
LISTEN_ADDR="${METRICS_ADDR:-0.0.0.0}"

# Ensure metrics directory exists
mkdir -p "$METRICS_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Generate combined metrics file
generate_metrics() {
    local output_file="${METRICS_DIR}/backup_metrics.prom"
    local temp_file="${output_file}.tmp"

    {
        echo "# HELP backup_rpo_lag_seconds WAL archiving lag in seconds"
        echo "# TYPE backup_rpo_lag_seconds gauge"
        if [ -f "${METRICS_DIR}/rpo_lag_seconds.prom" ]; then
            grep "^backup_rpo_lag_seconds " "${METRICS_DIR}/rpo_lag_seconds.prom" 2>/dev/null || echo "backup_rpo_lag_seconds -1"
        else
            echo "backup_rpo_lag_seconds -1"
        fi

        if [ -f "${METRICS_DIR}/rpo_lag_seconds.prom" ]; then
            grep "^backup_current_wal_info" "${METRICS_DIR}/rpo_lag_seconds.prom" 2>/dev/null || true
            grep "^backup_rpo_threshold_seconds " "${METRICS_DIR}/rpo_lag_seconds.prom" 2>/dev/null || true
        fi

        echo "# HELP backup_rto_last_seconds Last restore duration in seconds"
        echo "# TYPE backup_rto_last_seconds gauge"
        if [ -f "${METRICS_DIR}/rto_last_seconds" ]; then
            echo "backup_rto_last_seconds $(cat "${METRICS_DIR}/rto_last_seconds")"
        else
            echo "backup_rto_last_seconds -1"
        fi

        echo "# HELP backup_validation_status Validation status: 0=skipped, 1=success, 2=failed"
        echo "# TYPE backup_validation_status gauge"
        if [ -f "${METRICS_DIR}/validation_status.prom" ]; then
            grep "^backup_validation_status " "${METRICS_DIR}/validation_status.prom" 2>/dev/null || echo "backup_validation_status -1"
        else
            echo "backup_validation_status -1"
        fi

        echo "# HELP backup_quota_used_percent Percentage of backup quota used"
        echo "# TYPE backup_quota_used_percent gauge"
        if [ -f "${METRICS_DIR}/quota_usage.prom" ]; then
            grep "^backup_quota_used_percent " "${METRICS_DIR}/quota_usage.prom" 2>/dev/null || echo "backup_quota_used_percent -1"
        else
            echo "backup_quota_used_percent -1"
        fi

        echo "# HELP backup_success_total Total number of successful backups"
        echo "# TYPE backup_success_total counter"
        SUCCESS_COUNT="$(find /backup/daily \( -name '*.dump.gz.enc' -o -name '*.dump.gz' \) -type f 2>/dev/null | wc -l)" || SUCCESS_COUNT=0
        echo "backup_success_total ${SUCCESS_COUNT}"

        echo "# HELP backup_job_last_success Unix timestamp of last successful job run"
        echo "# TYPE backup_job_last_success gauge"
        for job_file in "${METRICS_DIR}"/job_*_last_success.prom; do
            if [ -f "$job_file" ]; then
                grep "^backup_job_last_success{" "$job_file" 2>/dev/null || true
            fi
        done

        echo "# HELP backup_job_last_duration Duration of last job run in seconds"
        echo "# TYPE backup_job_last_duration gauge"
        for job_file in "${METRICS_DIR}"/job_*_last_duration.prom; do
            if [ -f "$job_file" ]; then
                grep "^backup_job_last_duration{" "$job_file" 2>/dev/null || true
            fi
        done

        echo "# HELP backup_exporter_info Metrics exporter info"
        echo "# TYPE backup_exporter_info gauge"
        echo "backup_exporter_info{version=\"1.0.0\"} 1"
    } > "$temp_file"

    mv "$temp_file" "$output_file"
}

# Simple HTTP server using netcat
serve_metrics() {
    log "Starting backup metrics exporter on ${LISTEN_ADDR}:${LISTEN_PORT}"

    while true; do
        generate_metrics

        # Use nc to serve a single request (OpenBSD netcat syntax: nc -l [host] [port])
        { echo -e "HTTP/1.1 200 OK\r\nContent-Type: text/plain; charset=utf-8\r\nConnection: close\r\n\r\n"; cat "${METRICS_DIR}/backup_metrics.prom"; } | \
            timeout 3 nc -lN "$LISTEN_ADDR" "$LISTEN_PORT" -w 1 2>/dev/null || true
    done
}

# Alternative: if nc is not available, use a simple loop with bash
serve_metrics_bash() {
    log "Starting backup metrics exporter on ${LISTEN_ADDR}:${LISTEN_PORT} (bash fallback)"

    while true; do
        generate_metrics

        # Use /dev/tcp for a simple HTTP response
        {
            exec 3<>/dev/tcp/${LISTEN_ADDR}/${LISTEN_PORT}
            while read -r line <&3; do
                if [[ "$line" == $'\r' ]]; then
                    break
                fi
            done

            echo -e "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\n" >&3
            cat "${METRICS_DIR}/backup_metrics.prom" >&3
            exec 3<&-
            exec 3>&-
        } 2>/dev/null || true
    done
}

# Try nc first, fallback to bash
case "${1:-}" in
    --once)
        generate_metrics
        cat "${METRICS_DIR}/backup_metrics.prom"
        ;;
    *)
        if command -v nc >/dev/null 2>&1; then
            serve_metrics
        else
            serve_metrics_bash
        fi
        ;;
esac
