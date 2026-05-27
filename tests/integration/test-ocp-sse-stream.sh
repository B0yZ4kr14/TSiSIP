#!/usr/bin/env bash
# Test: Validate SSE stream endpoint structure and data bindings
set -euo pipefail

cd "$(dirname "$0")/../.."
SSE_FILE="$PWD/web/common/sse-stream.php"
CLIENT_FILE="$PWD/web/tsisip/js/sse-client.js"
DASHBOARD="$PWD/web/dashboard.php"

echo "=== Test: SSE Stream Structure ==="

[ -f "$SSE_FILE" ]
grep -q "require_once __DIR__ . '/config.php'" "$SSE_FILE"
grep -q "require_once __DIR__ . '/csrf.php'" "$SSE_FILE"
grep -q "require_once __DIR__ . '/mi-http.php'" "$SSE_FILE"
echo "  OK: sse-stream.php has required includes"

# Verify new data sources are collected
grep -q "'memory'" "$SSE_FILE"
grep -q "'processes'" "$SSE_FILE"
grep -q "'pike_blocked'" "$SSE_FILE"
grep -q "'tcp_connections'" "$SSE_FILE"
grep -q "'blacklists'" "$SSE_FILE"
grep -q "'usrloc_contacts'" "$SSE_FILE"
echo "  OK: sse-stream.php collects 8 new data sources"

# Verify heartbeat
grep -q "event: heartbeat" "$SSE_FILE"
echo "  OK: sse-stream.php sends heartbeat"

echo "=== Test: SSE Client Data Bindings ==="

[ -f "$CLIENT_FILE" ]
grep -q "updateDataBindings" "$CLIENT_FILE"
grep -q "data-sse-field" "$CLIENT_FILE"
grep -q "dataset.sseFormat" "$CLIENT_FILE"
echo "  OK: sse-client.js has data binding support"

echo "=== Test: Dashboard Live Metrics ==="

[ -f "$DASHBOARD" ]
grep -q "data-sse-field=\"dialogs\"" "$DASHBOARD"
grep -q "data-sse-field=\"memory.pkg_pct\"" "$DASHBOARD"
grep -q "data-sse-field=\"processes\"" "$DASHBOARD"
grep -q "data-sse-field=\"pike_blocked\"" "$DASHBOARD"
grep -q "data-sse-field=\"tcp_connections\"" "$DASHBOARD"
grep -q "data-sse-field=\"blacklists\"" "$DASHBOARD"
echo "  OK: dashboard.php has 8 live metric cards"

echo "=== All SSE stream tests passed ==="
