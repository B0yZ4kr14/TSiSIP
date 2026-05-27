#!/usr/bin/env bash
# TSiSIP OCP — Smoke Test: Verify all PHP pages have valid structure
set -uo pipefail

cd "$(dirname "$0")/../.."

echo "=== OCP Smoke Test: All pages structure validation ==="

FAIL=0
PASS=0

for page in web/*.php web/common/*.php; do
    basename_page=$(basename "$page")
    [ "$basename_page" = "index.php" ] && continue
    
    firstline=$(head -n 1 "$page" 2>/dev/null || true)
    [ -z "$firstline" ] && continue
    
    # Skip CLI-only scripts
    if echo "$firstline" | grep -q "#!/usr/bin/env php"; then
        continue
    fi
    
    # Must have PHP open tag
    if ! echo "$firstline" | grep -q "<?php"; then
        echo "  FAIL: $basename_page (missing PHP tag)"
        ((FAIL++))
        continue
    fi
    
    echo "  OK: $basename_page"
    ((PASS++))
done

echo ""
echo "=== Results ==="
echo "Passed: $PASS"
echo "Failed: $FAIL"

if [ $FAIL -gt 0 ]; then
    exit 1
else
    echo "ALL SMOKE TESTS PASSED"
    exit 0
fi
