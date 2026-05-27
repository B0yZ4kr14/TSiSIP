#!/usr/bin/env bash
# TSiSIP Performance Benchmark Report Generator
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
REPORT_DIR="${PROJECT_DIR}/reports/benchmarks"
REPORT_FILE="${REPORT_DIR}/benchmark-$(date +%Y%m%d-%H%M%S).md"
BASELINE_FILE="${REPORT_DIR}/.baseline.json"

mkdir -p "$REPORT_DIR"

echo "=== TSiSIP Benchmark Report ==="
echo "Timestamp: $(date -Iseconds)"
echo ""

# Run SIP benchmark
echo "Running SIP benchmark..."
SIP_RESULT=$(bash "${PROJECT_DIR}/scripts/benchmark-sip.sh" 2>&1 | tail -1 || echo '{"benchmark":"sip","error":"failed"}')

# Run PostgreSQL benchmark
echo "Running PostgreSQL benchmark..."
PG_RESULT=$(bash "${PROJECT_DIR}/scripts/benchmark-pgsql.sh" 2>&1 | tail -1 || echo '{"benchmark":"pgsql","error":"failed"}')

# Parse results
SIP_TIMESTAMP=$(echo "$SIP_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('timestamp','N/A'))" 2>/dev/null || echo "N/A")
SIP_OPTIONS=$(echo "$SIP_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('options_ms','N/A'))" 2>/dev/null || echo "N/A")
SIP_REGISTER=$(echo "$SIP_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('register_rps','N/A'))" 2>/dev/null || echo "N/A")
SIP_INVITE=$(echo "$SIP_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('invite_rps','N/A'))" 2>/dev/null || echo "N/A")

PG_TIMESTAMP=$(echo "$PG_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('timestamp','N/A'))" 2>/dev/null || echo "N/A")
PG_SINGLE=$(echo "$PG_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('single_query_ms','N/A'))" 2>/dev/null || echo "N/A")
PG_LOOKUP=$(echo "$PG_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('lookup_ms','N/A'))" 2>/dev/null || echo "N/A")
PG_CONN=$(echo "$PG_RESULT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('connection_overhead_ms','N/A'))" 2>/dev/null || echo "N/A")

# Load baseline for comparison
REGRESSION=""
if [[ -f "$BASELINE_FILE" ]]; then
    BASELINE=$(cat "$BASELINE_FILE")
    BASE_SIP_OPTIONS=$(echo "$BASELINE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('sip_options_ms','N/A'))" 2>/dev/null || echo "N/A")
    BASE_PG_SINGLE=$(echo "$BASELINE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('pg_single_ms','N/A'))" 2>/dev/null || echo "N/A")
    BASE_PG_LOOKUP=$(echo "$BASELINE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('pg_lookup_ms','N/A'))" 2>/dev/null || echo "N/A")
    
    REGRESSION="| Metric | Baseline | Current | Delta |\n|--------|----------|---------|-------|\n"
    REGRESSION+="| OPTIONS latency | ${BASE_SIP_OPTIONS}ms | ${SIP_OPTIONS}ms | — |\n"
    REGRESSION+="| PG single query | ${BASE_PG_SINGLE}ms | ${PG_SINGLE}ms | — |\n"
    REGRESSION+="| PG lookup | ${BASE_PG_LOOKUP}ms | ${PG_LOOKUP}ms | — |\n"
fi

# Generate report
cat > "$REPORT_FILE" <<REPORT
# TSiSIP Performance Benchmark Report

**Date**: $(date -Iseconds)
**Commit**: $(cd "$PROJECT_DIR" && git rev-parse --short HEAD)
**Branch**: $(cd "$PROJECT_DIR" && git branch --show-current)

## SIP Benchmark

| Metric | Value |
|--------|-------|
| Timestamp | ${SIP_TIMESTAMP} |
| Target | OpenSIPS |
| OPTIONS latency | ${SIP_OPTIONS}ms |
| REGISTER throughput | ${SIP_REGISTER} |
| INVITE throughput | ${SIP_INVITE} |

## PostgreSQL Benchmark

| Metric | Value |
|--------|-------|
| Timestamp | ${PG_TIMESTAMP} |
| Single query latency | ${PG_SINGLE}ms |
| Indexed lookup latency | ${PG_LOOKUP}ms |
| Connection overhead (10x) | ${PG_CONN}ms avg |

## Baseline Comparison

${REGRESSION:-No baseline available. Run with --save-baseline to establish one.}

## Raw Data

\`\`\`json
{"sip": ${SIP_RESULT}, "pgsql": ${PG_RESULT}}
\`\`\`
REPORT

echo ""
echo "Report written to: $REPORT_FILE"

# Save baseline if requested
if [[ "${1:-}" == "--save-baseline" ]]; then
    cat > "$BASELINE_FILE" <<BASELINE
{"sip_options_ms":"${SIP_OPTIONS}","pg_single_ms":"${PG_SINGLE}","pg_lookup_ms":"${PG_LOOKUP}","timestamp":"$(date -Iseconds)"}
BASELINE
    echo "Baseline saved to: $BASELINE_FILE"
fi
