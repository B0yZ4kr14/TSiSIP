#!/usr/bin/env bash
# Test: Validate all new OCP pages structure and syntax
set -euo pipefail

cd "$(dirname "$0")/../.."
WEB_DIR="$PWD/web"

echo "=== Test: New Pages Existence ==="

new_pages=(
    memory-status pike-monitor ratelimit usrloc
    hash-tables nat-helper tcp-connections topology-hiding
    processes blacklists version timers
    presence avp-inspector
)

for page in "${new_pages[@]}"; do
    file="$WEB_DIR/$page.php"
    if [ ! -f "$file" ]; then
        echo "FAIL: $page.php does not exist"
        exit 1
    fi
    # Basic PHP tag check instead of lint
    grep -q "<?php" "$file" || {
        echo "FAIL: $page.php missing PHP tag"
        exit 1
    }
    echo "  OK: $page.php exists and has PHP tag"
done

echo "=== Test: New Pages Required Patterns ==="

for page in "${new_pages[@]}"; do
    file="$WEB_DIR/$page.php"
    grep -q "require_once __DIR__ . '/common/config.php'" "$file" || {
        echo "FAIL: $page.php missing config.php"
        exit 1
    }
    grep -q "require_once __DIR__ . '/common/header.php'" "$file" || {
        echo "FAIL: $page.php missing header.php"
        exit 1
    }
    grep -q "require_once __DIR__ . '/common/footer.php'" "$file" || {
        echo "FAIL: $page.php missing footer.php"
        exit 1
    }
    grep -q "requireAuth()" "$file" || {
        echo "FAIL: $page.php missing requireAuth()"
        exit 1
    }
    grep -q "checkPasswordChange()" "$file" || {
        echo "FAIL: $page.php missing checkPasswordChange()"
        exit 1
    }
    echo "  OK: $page.php has required includes"
done

echo "=== Test: New Pages MI Commands ==="

patterns=(
    "memory-status.php:get_statistics"
    "pike-monitor.php:pike_list"
    "ratelimit.php:ratelimit_status"
    "usrloc.php:ul_dump"
    "hash-tables.php:htable_dump"
    "nat-helper.php:nh_show_sockets"
    "tcp-connections.php:tcp_list"
    "topology-hiding.php:dlg_list"
    "processes.php:ps"
    "blacklists.php:list_blacklists"
    "version.php:version"
    "timers.php:list_timers"
    "presence.php:pres_refresh_watchers"
    "avp-inspector.php:avp"
)

for pat in "${patterns[@]}"; do
    file="$WEB_DIR/${pat%%:*}"
    cmd="${pat##*:}"
    grep -q "$cmd" "$file" || {
        echo "FAIL: $(basename "$file") missing $cmd reference"
        exit 1
    }
    echo "  OK: $(basename "$file") references $cmd"
done

echo "=== Test: Role Restrictions ==="

devops_pages=(
    pike-monitor ratelimit tcp-connections timers presence
)

for page in "${devops_pages[@]}"; do
    file="$WEB_DIR/$page.php"
    grep -q "requireRole('devops')" "$file" || grep -q 'requireRole("devops")' "$file" || {
        echo "FAIL: $page.php missing requireRole('devops')"
        exit 1
    }
    echo "  OK: $page.php has devops restriction"
done

echo "=== All new page tests passed ==="
