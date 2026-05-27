#!/usr/bin/env bash
# Test: Validate MI export endpoint
set -euo pipefail

cd "$(dirname "$0")/../.."
EXPORTER="$PWD/web/common/export-mi.php"

echo "=== Test: Export Endpoint Structure ==="

[ -f "$EXPORTER" ]
grep -q "require_once __DIR__ . '/config.php'" "$EXPORTER"
grep -q "require_once __DIR__ . '/csrf.php'" "$EXPORTER"
grep -q "require_once __DIR__ . '/mi-http.php'" "$EXPORTER"
echo "  OK: export-mi.php has required includes"

# Verify exportable whitelist has expected commands
grep -q "'pike_list'" "$EXPORTER"
grep -q "'ratelimit_status'" "$EXPORTER"
grep -q "'tcp_list'" "$EXPORTER"
grep -q "'ps'" "$EXPORTER"
grep -q "'version'" "$EXPORTER"
echo "  OK: export-mi.php has expected commands"

# Verify format handling
grep -q "format === 'csv'" "$EXPORTER"
grep -q "application/json" "$EXPORTER"
echo "  OK: export-mi.php handles CSV and JSON"

# Verify pages have export buttons
pages=(pike-monitor ratelimit usrloc processes blacklists tcp-connections timers version memory-status hash-tables nat-helper topology-hiding presence)
for page in "${pages[@]}"; do
    grep -q "Export CSV" "$PWD/web/$page.php" || {
        echo "FAIL: $page.php missing Export CSV button"
        exit 1
    }
    grep -q "Export JSON" "$PWD/web/$page.php" || {
        echo "FAIL: $page.php missing Export JSON button"
        exit 1
    }
    echo "  OK: $page.php has export buttons"
done

echo "=== Test: mi-actions.js export helper ==="
JS_FILE="$PWD/web/tsisip/js/mi-actions.js"
grep -q "TSiSIPMi.exportData" "$JS_FILE"
echo "  OK: mi-actions.js has exportData helper"

echo "=== All MI export tests passed ==="
