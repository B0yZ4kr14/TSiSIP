#!/usr/bin/env bash
# TSiSIP SIP Signaling Benchmark
# Measures REGISTER and INVITE throughput using sipsak
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

OPENSIPS_HOST="${OPENSIPS_HOST:-localhost}"
OPENSIPS_PORT="${OPENSIPS_PORT:-5060}"
REGISTER_DURATION="${REGISTER_DURATION:-10}"
INVITE_DURATION="${INVITE_DURATION:-10}"
CONCURRENT="${CONCURRENT:-10}"

echo "=== TSiSIP SIP Benchmark ==="
echo "Target: ${OPENSIPS_HOST}:${OPENSIPS_PORT}"
echo "Timestamp: $(date -Iseconds)"
echo ""

# Check if sipsak is available
if ! command -v sipsak >/dev/null 2>&1; then
    echo "WARNING: sipsak not found. Installing..."
    if command -v apk >/dev/null 2>&1; then
        apk add --no-cache sipsak >/dev/null 2>&1 || true
    elif command -v apt-get >/dev/null 2>&1; then
        apt-get update && apt-get install -y sipsak >/dev/null 2>&1 || true
    fi
fi

# Verify OpenSIPS is reachable
echo "Checking OpenSIPS health..."
if ! docker compose -f "${PROJECT_DIR}/docker-compose.yml" ps opensips 2>/dev/null | grep -q "healthy\|Up"; then
    echo "WARNING: OpenSIPS may not be running. Starting services..."
    docker compose -f "${PROJECT_DIR}/docker-compose.yml" up -d opensips >/dev/null 2>&1 || true
    sleep 3
fi

# OPTIONS probe for baseline latency
echo ""
echo "--- OPTIONS Latency ---"
START=$(date +%s%N)
if docker run --rm --network "tsisip_sip_edge" alpine sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:${OPENSIPS_HOST}:${OPENSIPS_PORT} -vv" >/dev/null 2>&1; then
    END=$(date +%s%N)
    LATENCY_MS=$(( (END - START) / 1000000 ))
    echo "OPTIONS response time: ${LATENCY_MS}ms"
else
    echo "OPTIONS probe failed (OpenSIPS may not be fully ready)"
    LATENCY_MS="N/A"
fi

# REGISTER benchmark
echo ""
echo "--- REGISTER Throughput ---"
if command -v sipsak >/dev/null 2>&1; then
    REGISTER_RESULT=$(sipsak -s "sip:benchmark@${OPENSIPS_HOST}:${OPENSIPS_PORT}" \
        -l "$CONCURRENT" \
        -d "$REGISTER_DURATION" \
        -vv 2>&1 || true)
    REGISTER_RPS=$(echo "$REGISTER_RESULT" | grep -oP '\d+\.\d+ req/s' | head -1 || echo "N/A")
    echo "REGISTER RPS: ${REGISTER_RPS}"
else
    echo "sipsak not available — skipping REGISTER benchmark"
    REGISTER_RPS="N/A"
fi

# INVITE benchmark
echo ""
echo "--- INVITE Throughput ---"
if command -v sipsak >/dev/null 2>&1; then
    INVITE_RESULT=$(sipsak -s "sip:benchmark@${OPENSIPS_HOST}:${OPENSIPS_PORT}" \
        -l "$CONCURRENT" \
        -d "$INVITE_DURATION" \
        -i \
        -vv 2>&1 || true)
    INVITE_RPS=$(echo "$INVITE_RESULT" | grep -oP '\d+\.\d+ req/s' | head -1 || echo "N/A")
    echo "INVITE RPS: ${INVITE_RPS}"
else
    echo "sipsak not available — skipping INVITE benchmark"
    INVITE_RPS="N/A"
fi

# Output results as JSON for report parser
echo ""
echo '{"benchmark":"sip","timestamp":"'"$(date -Iseconds)"'","options_ms":"'"${LATENCY_MS}"'","register_rps":"'"${REGISTER_RPS}"'","invite_rps":"'"${INVITE_RPS}"'","target":"'"${OPENSIPS_HOST}:${OPENSIPS_PORT}"'"}'
