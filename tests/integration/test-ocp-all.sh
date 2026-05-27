#!/usr/bin/env bash
# TSiSIP OCP — Run All Integration Tests
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
echo "=== TSiSIP OCP Integration Test Suite ==="
echo "Target: $BASE"
echo "Time: $(date)"
echo ""

PASS=0
FAIL=0

run_test() {
    local script="$1"
    echo "--- Running: $(basename "$script") ---"
    if bash "$script" 2>&1; then
        ((PASS++))
        echo "✓ PASSED"
    else
        ((FAIL++))
        echo "✗ FAILED"
    fi
    echo ""
}

# Page tests
for test in "$(dirname "$0")"/test-ocp-*.sh; do
    if [ "$test" != "$(dirname "$0")/test-ocp-all.sh" ]; then
        run_test "$test"
    fi
done

echo "=== Results ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"
echo "Total:  $((PASS + FAIL))"

if [ $FAIL -gt 0 ]; then
    echo "SOME TESTS FAILED"
    exit 1
else
    echo "ALL TESTS PASSED"
    exit 0
fi
