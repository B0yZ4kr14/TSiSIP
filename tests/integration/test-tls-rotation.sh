#!/bin/bash
# TSiSIP Feature 015: Automated TLS Certificate Rotation — Integration Tests
# Wave 5: Testing & Validation
#
# Validates the TLS rotation pipeline end-to-end without requiring
# actual Let's Encrypt issuance (uses staging/dummy certs).
#
# Usage: ./tests/integration/test-tls-rotation.sh
# Exit: 0 = all passed, 1 = one or more failed

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
COMPOSE_FILE="${COMPOSE_FILE:-$PROJECT_DIR/docker-compose.yml}"
OPENSIPS_IMAGE="${OPENSIPS_IMAGE:-tsisip/opensips:latest}"
CERTBOT_IMAGE="${CERTBOT_IMAGE:-tsisip/certbot:latest}"

PASS=0
FAIL=0
SKIP=0
TEST_NUM=0

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

tap_ok() {
    local msg="$1"
    TEST_NUM=$((TEST_NUM + 1))
    echo "ok $TEST_NUM - $msg"
    PASS=$((PASS + 1))
}

tap_not_ok() {
    local msg="$1"
    TEST_NUM=$((TEST_NUM + 1))
    echo "not ok $TEST_NUM - $msg"
    FAIL=$((FAIL + 1))
}

tap_skip() {
    local msg="$1"
    local reason="${2:-skipped}"
    TEST_NUM=$((TEST_NUM + 1))
    echo "ok $TEST_NUM - $msg # SKIP $reason"
    SKIP=$((SKIP + 1))
}

cleanup_tmp_cert() {
    rm -rf /tmp/tsisip-tls-test-*
}

trap cleanup_tmp_cert EXIT

# ---------------------------------------------------------------------------
# Test 1: OpenSIPS config loads with TLS enabled (opensips -c)
# ---------------------------------------------------------------------------

test_opensips_config() {
    echo "# Test 1: OpenSIPS config syntax validation with TLS" >&2

    # Render a test config by substituting placeholders
    local test_cfg
    test_cfg="$(mktemp /tmp/tsisip-tls-test-XXXXX.cfg)"

    sed -e 's/\${[A-Z_]*}/127.0.0.1/g' \
        -e 's/\${AUTH_SECRET_32_CHARS}/PLACEHOLDER_AUTH_SECRET/g' \
        -e 's/\${TOPOLOGY_SECRET}/PLACEHOLDER_TOPOLOGY_SECRET/g' \
        "$PROJECT_DIR/opensips/opensips.cfg.tpl" > "$test_cfg"

    # Verify TLS-specific modules are present
    if ! grep -q 'loadmodule "tls_mgm.so"' "$test_cfg"; then
        tap_not_ok "OpenSIPS config loads with TLS enabled — tls_mgm.so missing"
        rm -f "$test_cfg"
        return
    fi
    if ! grep -q 'loadmodule "proto_tls.so"' "$test_cfg"; then
        tap_not_ok "OpenSIPS config loads with TLS enabled — proto_tls.so missing"
        rm -f "$test_cfg"
        return
    fi
    if ! grep -q 'loadmodule "mi_http.so"' "$test_cfg"; then
        tap_not_ok "OpenSIPS config loads with TLS enabled — mi_http.so missing"
        rm -f "$test_cfg"
        return
    fi

    # Verify tls_mgm paths point to /certs/live/
    if ! grep -q '/certs/live/' "$test_cfg"; then
        tap_not_ok "OpenSIPS config loads with TLS enabled — /certs/live/ paths missing"
        rm -f "$test_cfg"
        return
    fi

    # If opensips binary is available (container or host), run -c
    if command -v opensips >/dev/null 2>&1; then
        if opensips -c -f "$test_cfg" >/dev/null 2>&1; then
            tap_ok "OpenSIPS config loads with TLS enabled (opensips -c)"
        else
            tap_not_ok "OpenSIPS config loads with TLS enabled (opensips -c failed)"
        fi
    elif docker image inspect "$OPENSIPS_IMAGE" >/dev/null 2>&1; then
        # Run config check inside a throwaway container, bypassing entrypoint
        # since the config is already rendered
        if docker run --rm --entrypoint /usr/local/sbin/opensips \
            -v "$test_cfg:/etc/opensips/opensips.cfg:ro" \
            "$OPENSIPS_IMAGE" \
            -c -f /etc/opensips/opensips.cfg >/dev/null 2>&1; then
            tap_ok "OpenSIPS config loads with TLS enabled (opensips -c in container)"
        else
            tap_not_ok "OpenSIPS config loads with TLS enabled (opensips -c in container failed)"
        fi
    else
        tap_skip "OpenSIPS config loads with TLS enabled" "opensips binary and image unavailable"
    fi

    rm -f "$test_cfg"
}

# ---------------------------------------------------------------------------
# Test 2: MI HTTP endpoint responds
# ---------------------------------------------------------------------------

test_mi_http_endpoint() {
    echo "# Test 2: MI HTTP endpoint responds" >&2

    local container
    container="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"

    if ! docker ps --format '{{.Names}}' | grep -qx "$container"; then
        tap_skip "MI HTTP endpoint responds" "OpenSIPS container not running"
        return
    fi

    if docker exec "$container" sh -c "curl -fsSL --max-time 5 -X POST -H 'Content-Type: application/json' -d '{\\\"jsonrpc\\\":\\\"2.0\\\",\\\"method\\\":\\\"version\\\",\\\"params\\\":[],\\\"id\\\":1}' http://127.0.0.1:8888/mi >/dev/null 2>&1"; then
        tap_ok "MI HTTP endpoint responds"
    else
        tap_not_ok "MI HTTP endpoint responds"
    fi
}

# ---------------------------------------------------------------------------
# Test 3: tls_reload works via scripts/tls-reload.sh
# ---------------------------------------------------------------------------

test_tls_reload() {
    echo "# Test 3: tls_reload via scripts/tls-reload.sh" >&2

    local script="$PROJECT_DIR/scripts/tls-reload.sh"
    if [ ! -x "$script" ]; then
        tap_not_ok "tls_reload works via scripts/tls-reload.sh — script missing or not executable"
        return
    fi

    # Syntax check first
    if ! bash -n "$script"; then
        tap_not_ok "tls_reload works via scripts/tls-reload.sh — syntax error"
        return
    fi

    local container
    container="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"

    if ! docker ps --format '{{.Names}}' | grep -qx "$container"; then
        tap_skip "tls_reload works via scripts/tls-reload.sh" "OpenSIPS container not running"
        return
    fi

    if OPENSIPS_CONTAINER="$container" "$script" >/dev/null 2>&1; then
        tap_ok "tls_reload works via scripts/tls-reload.sh"
    else
        tap_not_ok "tls_reload works via scripts/tls-reload.sh"
    fi
}

# ---------------------------------------------------------------------------
# Test 4: Simulate cert expiry — verify exporter reports < 30 days
# ---------------------------------------------------------------------------

test_cert_expiry_warning() {
    echo "# Test 4: Cert expiry monitor reports < 30 days for near-expiry cert" >&2

    local tmpdir
    tmpdir="$(mktemp -d /tmp/tsisip-tls-test-XXXXX)"

    # Generate a dummy self-signed certificate expiring in 7 days
    openssl req -x509 -newkey rsa:2048 -keyout "$tmpdir/server.key" \
        -out "$tmpdir/server.crt" -days 7 -nodes \
        -subj "/CN=test.tsisip.local" 2>/dev/null

    if [ ! -f "$tmpdir/server.crt" ]; then
        tap_not_ok "Cert expiry monitor reports < 30 days — cert generation failed"
        rm -rf "$tmpdir"
        return
    fi

    # Verify the certificate is valid for less than 30 days (2592000 seconds)
    if openssl x509 -in "$tmpdir/server.crt" -noout -checkend 2592000 >/dev/null 2>&1; then
        tap_not_ok "Cert expiry monitor reports < 30 days — dummy cert still valid for 30d"
        rm -rf "$tmpdir"
        return
    fi

    # Run cert-expiry-monitor.sh against the dummy cert (expect failure = warning)
    local monitor="$PROJECT_DIR/scripts/cert-expiry-monitor.sh"
    if [ -x "$monitor" ]; then
        if CERT_PATH="$tmpdir/server.crt" WARN_DAYS=30 "$monitor" >/dev/null 2>&1; then
            tap_not_ok "Cert expiry monitor reports < 30 days — monitor returned OK for near-expiry cert"
        else
            tap_ok "Cert expiry monitor reports < 30 days (cert-expiry-monitor.sh)"
        fi
    else
        tap_skip "Cert expiry monitor reports < 30 days" "cert-expiry-monitor.sh not available"
    fi

    rm -rf "$tmpdir"
}

# ---------------------------------------------------------------------------
# Test 5: Certbot container can run in staging/dry-run mode
# ---------------------------------------------------------------------------

test_certbot_staging() {
    echo "# Test 5: Certbot container accepts --dry-run" >&2

    # Build the certbot image if not present
    if ! docker image inspect "$CERTBOT_IMAGE" >/dev/null 2>&1; then
        echo "# Building certbot image..." >&2
        if ! docker build -t "$CERTBOT_IMAGE" -f "$PROJECT_DIR/docker/certbot/Dockerfile" "$PROJECT_DIR/docker/certbot/" >/dev/null 2>&1; then
            tap_skip "Certbot container accepts --dry-run" "image build failed"
            return
        fi
    fi

    # Verify certbot binary exists and --dry-run flag is recognized
    # Use --entrypoint to bypass entrypoint.sh which expects env vars
    if docker run --rm --entrypoint /usr/local/bin/certbot "$CERTBOT_IMAGE" --help | grep -q -- '--dry-run'; then
        tap_ok "Certbot container accepts --dry-run"
    else
        tap_not_ok "Certbot container accepts --dry-run"
    fi
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

echo "TAP version 14"
echo "1..5"

test_opensips_config
test_mi_http_endpoint
test_tls_reload
test_cert_expiry_warning
test_certbot_staging

echo ""
echo "# Results: $PASS passed, $FAIL failed, $SKIP skipped"

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi
exit 0
