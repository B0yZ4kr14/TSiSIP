#!/bin/bash
# TSiSIP OCP Page Load Benchmark
# Measures HTTP response time for critical pages

set -euo pipefail

BASE_URL="${BASE_URL:-https://tsiapp.io/TSiSIP}"
ITERATIONS="${ITERATIONS:-50}"

echo "=== TSiSIP OCP Page Load Benchmark ==="
echo "Target: ${BASE_URL}"
echo "Iterations: ${ITERATIONS}"
echo ""

if ! command -v curl >/dev/null 2>&1; then
    echo "ERROR: curl not found"
    exit 1
fi

pages=(
    "/login.php"
    "/"
)

for page in "${pages[@]}"; do
    url="${BASE_URL}${page}"
    echo "Benchmarking: ${url}"
    total=0
    min=999999
    max=0
    failures=0

    for i in $(seq 1 ${ITERATIONS}); do
        duration=$(curl -sf -o /dev/null -w "%{time_total}" "${url}" 2>/dev/null || echo "fail")
        if [ "${duration}" != "fail" ]; then
            ms=$(echo "${duration} * 1000 / 1" | bc)
            total=$((total + ms))
            if (( ms < min )); then min=$ms; fi
            if (( ms > max )); then max=$ms; fi
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
