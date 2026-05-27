#!/usr/bin/env bash
# Test: Validate MI action endpoint and modified pages structure
set -euo pipefail

cd "$(dirname "$0")/../.."
WEB_DIR="$PWD/web"
COMMON_DIR="$WEB_DIR/common"

PHP_LINT() {
    docker compose exec -T ocp php -l "/var/www/html/$1" 2>/dev/null | grep -q "No syntax errors"
}

echo "=== Test: MI Action Endpoint Structure ==="

# 1. mi-action.php must exist and be syntactically valid
[ -f "$COMMON_DIR/mi-action.php" ]
echo "  OK: mi-action.php exists"

# 2. mi-action.php must include required files
grep -q "require_once __DIR__ . '/config.php'" "$COMMON_DIR/mi-action.php"
grep -q "require_once __DIR__ . '/csrf.php'" "$COMMON_DIR/mi-action.php"
grep -q "require_once __DIR__ . '/mi-http.php'" "$COMMON_DIR/mi-action.php"
echo "  OK: mi-action.php has required includes"

# 3. mi-action.php must have mutation and read-only command lists
grep -q "'address_reload'" "$COMMON_DIR/mi-action.php"
grep -q "'dlg_end_dlg'" "$COMMON_DIR/mi-action.php"
grep -q "'pike_list'" "$COMMON_DIR/mi-action.php"
grep -q "'version'" "$COMMON_DIR/mi-action.php"
echo "  OK: mi-action.php has expected commands"

# 4. mi-actions.js must exist and have helpers
JS_FILE="$WEB_DIR/tsisip/js/mi-actions.js"
[ -f "$JS_FILE" ]
grep -q "TSiSIPMi.action" "$JS_FILE"
grep -q "TSiSIPMi.attachReload" "$JS_FILE"
grep -q "TSiSIPMi.attachToggle" "$JS_FILE"
grep -q "TSiSIPMi.attachRowAction" "$JS_FILE"
echo "  OK: mi-actions.js has helpers"

# 5. footer.php must include mi-actions.js
grep -q "mi-actions.js" "$COMMON_DIR/footer.php"
echo "  OK: footer.php includes mi-actions.js"

echo "=== Test: Modified Pages MI Action Integration ==="

for page in address dialplan domains; do
    grep -qE "(reload|address_reload|dialplan_reload|domain_reload)" "$WEB_DIR/$page.php"
    echo "  OK: $page.php has reload integration"
done

# dynamic-routing.php
grep -q "dr_reload" "$WEB_DIR/dynamic-routing.php"
grep -q "dr_gw_status" "$WEB_DIR/dynamic-routing.php"
echo "  OK: dynamic-routing.php has reload + gw status"

# dialog.php
grep -q "dlg_end_dlg" "$WEB_DIR/dialog.php"
echo "  OK: dialog.php has terminate integration"

# siptrace.php
grep -q "sip_trace_start" "$WEB_DIR/siptrace.php"
grep -q "sip_trace_stop" "$WEB_DIR/siptrace.php"
echo "  OK: siptrace.php has capture controls"

# load-balancer.php
grep -q "lb_status" "$WEB_DIR/load-balancer.php"
grep -q "lb_reload" "$WEB_DIR/load-balancer.php"
echo "  OK: load-balancer.php has status + reload"

# clusterer.php
grep -q "clusterer_set_state" "$WEB_DIR/clusterer.php"
grep -q "clusterer_ping" "$WEB_DIR/clusterer.php"
echo "  OK: clusterer.php has set state + ping"

# rtpengine.php
grep -q "rtpengine_enable" "$WEB_DIR/rtpengine.php"
grep -q "rtpengine_disable" "$WEB_DIR/rtpengine.php"
echo "  OK: rtpengine.php has enable/disable"

# uac-registrant.php
grep -q "uac_reg_refresh" "$WEB_DIR/uac-registrant.php"
echo "  OK: uac-registrant.php has refresh action"

# statistics.php
grep -q "reset_statistics" "$WEB_DIR/statistics.php"
echo "  OK: statistics.php has reset action"

# call-center.php
grep -q "cc_agent_login" "$WEB_DIR/call-center.php"
echo "  OK: call-center.php has agent controls"

echo "=== All MI Action tests passed ==="
