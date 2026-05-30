#!/usr/bin/env bash
# TSiSIP Fast Test Suite
# Runs all tests that do NOT require a running Docker stack.
# These tests validate code structure, accessibility, requirement IDs, and static analysis.

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

cd "$PROJECT_DIR"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASSED=0
FAILED=0
SKIPPED=0

run_test() {
    local name="$1"
    local cmd="$2"
    echo -n "  ${name} ... "
    if output=$(eval "$cmd" 2>&1); then
        echo -e "${GREEN}PASS${NC}"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}FAIL${NC}"
        echo "    $output" | sed 's/^/    /'
        FAILED=$((FAILED + 1))
    fi
}

echo "========================================"
echo "TSiSIP Fast Test Suite"
echo "========================================"
echo ""

# --- JavaScript / Node.js tests ---
run_test "Accessibility Audit" "node tests/accessibility-audit.test.js"
run_test "D3.js + jQuery Coexistence" "node tests/d3-jquery-coexistence.test.js"
run_test "OCP Critical Pages" "node tests/integration/test_ocp_critical_pages.js"
run_test "Requirement ID Format" "node tests/integration/test-requirement-id-format.js"

# --- Shell tests ---
run_test "OCP Smoke Test" "bash tests/integration/test-ocp-smoke.sh"
run_test "OCP MI Export" "bash tests/integration/test-ocp-mi-export.sh"
run_test "OCP MI Whitelist" "bash tests/integration/test-ocp-mi-whitelist.sh"
run_test "OCP New Pages" "bash tests/integration/test-ocp-new-pages.sh"
run_test "OCP SSE Stream" "bash tests/integration/test-ocp-sse-stream.sh"

echo ""
echo "========================================"
echo "Summary"
echo "========================================"
echo -e "Passed:  ${GREEN}${PASSED}${NC}"
echo -e "Failed:  ${RED}${FAILED}${NC}"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}All fast tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed.${NC}"
    exit 1
fi
