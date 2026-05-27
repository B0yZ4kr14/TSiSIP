#!/usr/bin/env bash
# Test: Validate MI command whitelist size and coverage
set -euo pipefail

cd "$(dirname "$0")/../.."
MI_ACTION="$PWD/web/common/mi-action.php"
MI_COMMANDS="$PWD/web/mi-commands.php"

echo "=== Test: MI Action Whitelist Size ==="

# Count unique commands in whitelist
whitelist_count=$(grep -oE "'[a-z_]+'" "$MI_ACTION" | tr -d "'" | sort -u | wc -l)
echo "  Total unique commands: $whitelist_count"

if [ "$whitelist_count" -lt 40 ]; then
    echo "FAIL: Whitelist has only $whitelist_count commands, expected >= 40"
    exit 1
fi
echo "  OK: Whitelist >= 40"

echo "=== Test: mi-commands.php Whitelist Coverage ==="

for cmd in address_reload dlg_end_dlg pike_list version ps dr_reload sip_trace_start lb_status clusterer_ping rtpengine_enable uac_reg_refresh reset_statistics cc_agent_login ratelimit_reset htable_flush pres_refresh_watchers tcp_list list_blacklists list_timers ul_dump which; do
    grep -q "'$cmd'" "$MI_COMMANDS" || {
        echo "FAIL: mi-commands.php missing command: $cmd"
        exit 1
    }
    echo "  OK: $cmd present"
done

echo "=== Test: mi-commands.php Categories ==="

# Must have a diverse set of categories
categories=$(grep -oP "'category'\s*=>\s*'\K[^']+" "$MI_COMMANDS" | sort -u | wc -l)
echo "  Unique categories: $categories"

if [ "$categories" -lt 10 ]; then
    echo "FAIL: Only $categories categories found, expected >= 10"
    exit 1
fi
echo "  OK: Categories >= 10"

echo "=== Test: mi-commands.php Search/Filter ==="

grep -qi "search\|filter" "$MI_COMMANDS" || {
    echo "FAIL: mi-commands.php missing search/filter functionality"
    exit 1
}
echo "  OK: Search/filter present"

echo "=== All MI whitelist tests passed ==="
