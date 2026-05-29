#!/bin/bash
# TSiSIP PostgreSQL Query Performance Benchmark
# Measures latency of critical database queries

set -euo pipefail

DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-opensips}"
DB_USER="${DB_USER:-opensips}"
ITERATIONS="${ITERATIONS:-100}"

echo "=== TSiSIP PostgreSQL Benchmark ==="
echo "Target: ${DB_HOST}:${DB_PORT}/${DB_NAME}"
echo "Iterations: ${ITERATIONS}"
echo ""

if ! command -v psql >/dev/null 2>&1; then
    echo "ERROR: psql not found"
    exit 1
fi

queries=(
    "SELECT COUNT(*) FROM subscriber"
    "SELECT COUNT(*) FROM ocp_audit_log"
    "SELECT * FROM dispatcher LIMIT 10"
    "SELECT * FROM subscriber ORDER BY id DESC LIMIT 25"
    "SELECT COUNT(*) FROM ocp_audit_log WHERE event_time > NOW() - INTERVAL '24 hours'"
)

for q in "${queries[@]}"; do
    echo "Benchmarking: ${q}"
    total=0
    min=999999
    max=0
    failures=0

    for i in $(seq 1 ${ITERATIONS}); do
        start=$(date +%s%N)
        if PGPASSWORD="${DB_PASSWORD:-}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" -c "${q}" >/dev/null 2>&1; then
            end=$(date +%s%N)
            duration=$(( (end - start) / 1000000 ))
            total=$((total + duration))
            if (( duration < min )); then min=$duration; fi
            if (( duration > max )); then max=$duration; fi
        else
            failures=$((failures + 1))
        fi
    done

    successful=$((ITERATIONS - failures))
    if (( successful > 0 )); then
        avg=$((total / successful))
        echo "  Avg: ${avg}ms | Min: ${min}ms | Max: ${max}ms | Failures: ${failures}/${ITERATIONS}"
    else
        echo "  All ${ITERATIONS} requests failed"
    fi
done

echo ""
echo "=== Benchmark Complete ==="
