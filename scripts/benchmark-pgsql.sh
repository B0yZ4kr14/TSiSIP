#!/usr/bin/env bash
# TSiSIP PostgreSQL Benchmark
# Measures subscriber lookup query latency under load
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

ITERATIONS="${ITERATIONS:-1000}"
CONCURRENT="${CONCURRENT:-10}"

echo "=== TSiSIP PostgreSQL Benchmark ==="
echo "Timestamp: $(date -Iseconds)"
echo ""

# Verify PostgreSQL is running
if ! docker compose -f "${PROJECT_DIR}/docker-compose.yml" ps postgres 2>/dev/null | grep -q "healthy\|Up"; then
    echo "WARNING: PostgreSQL may not be running. Starting services..."
    docker compose -f "${PROJECT_DIR}/docker-compose.yml" up -d postgres >/dev/null 2>&1 || true
    # Warmup delay: allow PostgreSQL to warm shared_buffers before benchmark queries
sleep 3
fi

# Single-query latency test
echo "--- Single Query Latency ---"
START=$(date +%s%N)
RESULT=$(docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres \
    psql -U opensips -d opensips -tAc "SELECT COUNT(*) FROM subscriber" 2>/dev/null || echo "ERROR")
END=$(date +%s%N)
SINGLE_MS=$(( (END - START) / 1000000 ))
echo "Subscriber count query: ${RESULT} rows, ${SINGLE_MS}ms"

# Indexed lookup latency test
echo ""
echo "--- Indexed Lookup Latency ---"
START=$(date +%s%N)
RESULT=$(docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres \
    psql -U opensips -d opensips -tAc "SELECT username FROM subscriber WHERE username = 'devuser' LIMIT 1" 2>/dev/null || echo "ERROR")
END=$(date +%s%N)
LOOKUP_MS=$(( (END - START) / 1000000 ))
echo "Username lookup: ${RESULT}, ${LOOKUP_MS}ms"

# Connection overhead test
echo ""
echo "--- Connection Overhead ---"
START=$(date +%s%N)
for i in $(seq 1 10); do
    docker compose -f "${PROJECT_DIR}/docker-compose.yml" exec -T postgres \
        psql -U opensips -d opensips -tAc "SELECT 1" >/dev/null 2>&1 || true
done
END=$(date +%s%N)
CONN_MS=$(( (END - START) / 10000000 ))
echo "10 connection round-trips: ${CONN_MS}ms avg"

# Output results as JSON
echo ""
echo '{"benchmark":"pgsql","timestamp":"'"$(date -Iseconds)"'","single_query_ms":'"${SINGLE_MS}"',"lookup_ms":'"${LOOKUP_MS}"',"connection_overhead_ms":'"${CONN_MS}"'}'
