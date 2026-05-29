#!/bin/bash
# TSiSIP MI HTTP Performance Benchmark
# Measures latency of critical OpenSIPS MI commands

set -euo pipefail

MI_HOST="${MI_HOST:-opensips}"
MI_PORT="${MI_PORT:-8888}"
MI_URL="http://${MI_HOST}:${MI_PORT}/mi"
ITERATIONS="${ITERATIONS:-100}"

echo "=== TSiSIP MI HTTP Benchmark ==="
echo "Target: ${MI_URL}"
echo "Iterations: ${ITERATIONS}"
echo ""

if ! command -v curl >/dev/null 2>&1; then
    echo "ERROR: curl not found"
    exit 1
fi

commands=(
    "get_statistics"
    "ps"
    "list_blacklists"
    "version"
    "which"
)

for cmd in "${commands[@]}"; do
    echo "Benchmarking: ${cmd}"
    total=0
    min=999999
    max=0
    failures=0

    for i in $(seq 1 ${ITERATIONS}); do
        start=$(date +%s%N)
        if curl -sf "${MI_URL}/${cmd}" >/dev/null 2>&1; then
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
