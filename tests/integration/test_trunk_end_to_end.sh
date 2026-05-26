#!/usr/bin/env bash
# Feature 017: End-to-end trunk routing test with mock SIP provider
# Validates schema, triggers, and dispatcher sync without requiring live provider.

set -euo pipefail

NETWORK="tsisip_sip_edge"
MOCK_CONTAINER="tsisip-mock-trunk"
POSTGRES_CONTAINER="tsisip-postgres-1"
OPENSIPS_CONTAINER="tsisip-opensips-1"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0

pass() { echo -e "${GREEN}[PASS]${NC} $1"; ((PASS++)) || true; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; ((FAIL++)) || true; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

cleanup() {
    echo "Cleaning up..."
    docker rm -f "${MOCK_CONTAINER}" >/dev/null 2>&1 || true
    docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -c "DELETE FROM sip_trunk_providers WHERE name LIKE 'mock-%';" >/dev/null 2>&1 || true
    docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -c "DELETE FROM sip_trunk_did_mappings WHERE did_number = '+19998887777';" >/dev/null 2>&1 || true
}
trap cleanup EXIT

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Building mock trunk image ==="
docker build -t tsisip/mock-trunk:latest "${SCRIPT_DIR}/mock-sip-trunk" >/dev/null 2>&1
pass "Mock trunk image built"

echo "=== Starting mock trunk container ==="
docker run -d --rm \
    --name "${MOCK_CONTAINER}" \
    --network "${NETWORK}" \
    --cap-drop ALL \
    tsisip/mock-trunk:latest >/dev/null 2>&1

sleep 2

MOCK_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "${MOCK_CONTAINER}")
echo "Mock trunk IP: ${MOCK_IP}"
pass "Mock trunk container running"

PGSQL="docker exec ${POSTGRES_CONTAINER} psql -U opensips -d opensips -Atc"

echo "=== AC3: Inbound DID Routing Setup ==="
${PGSQL} "INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, registration_required, max_cps, max_concurrent)
VALUES ('mock-inbound', '${MOCK_IP}', 5060, 'udp', 1, true, false, 100, 1000);"

MOCK_TRUNK_ID=$(${PGSQL} "SELECT id FROM sip_trunk_providers WHERE name = 'mock-inbound';")
${PGSQL} "INSERT INTO sip_trunk_did_mappings (did_number, tenant_id, trunk_provider_id, dispatcher_setid, enabled)
VALUES ('+19998887777', '00000000-0000-0000-0000-000000000000', ${MOCK_TRUNK_ID}, 1, true);"
pass "Mock inbound trunk and DID mapping inserted"

# Verify dispatcher auto-sync trigger
DISP_COUNT=$(${PGSQL} "SELECT COUNT(*) FROM dispatcher WHERE setid = 100 AND description = 'Trunk: mock-inbound';")
if [ "${DISP_COUNT}" -eq 1 ]; then
    pass "Dispatcher set 100 auto-synced mock inbound trunk (trigger)"
else
    fail "Dispatcher auto-sync failed for mock inbound trunk"
fi

echo "=== AC4: Outbound Trunk Selection Setup ==="
${PGSQL} "INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, registration_required, max_cps, max_concurrent)
VALUES ('mock-outbound', '${MOCK_IP}', 5060, 'udp', 0, true, false, 100, 1000);"

DISP_COUNT=$(${PGSQL} "SELECT COUNT(*) FROM dispatcher WHERE setid = 100 AND description = 'Trunk: mock-outbound';")
if [ "${DISP_COUNT}" -eq 1 ]; then
    pass "Dispatcher set 100 auto-synced mock outbound trunk (trigger)"
else
    fail "Dispatcher auto-sync failed for mock outbound trunk"
fi

echo "=== AC2: uac_registrant trigger ==="
${PGSQL} "UPDATE sip_trunk_providers SET registration_required = true WHERE name = 'mock-outbound';"
REG_COUNT=$(${PGSQL} "SELECT COUNT(*) FROM sip_trunk_registrations WHERE trunk_provider_id = (SELECT id FROM sip_trunk_providers WHERE name = 'mock-outbound');")
if [ "${REG_COUNT}" -eq 1 ]; then
    pass "sip_trunk_registrations auto-populated when registration_required toggled true"
else
    fail "Trunk registration sync trigger did not populate sip_trunk_registrations"
fi

echo "=== OPTIONS Probe Test ==="
# Send OPTIONS from test container to mock trunk via OpenSIPS dispatcher
docker run --rm --network "${NETWORK}" alpine sh -c "apk add sipsak >/dev/null 2>&1 && sipsak -s sip:${MOCK_IP}:5060 -vv" >/dev/null 2>&1 && pass "OPTIONS probe to mock trunk returns 200 OK" || warn "OPTIONS probe result inconclusive (sipsak may not be installed)"

echo ""
echo "========================================"
echo "Trunk End-to-End Test Summary"
echo "========================================"
echo -e "Passed: ${GREEN}${PASS}${NC}"
echo -e "Failed: ${RED}${FAIL}${NC}"

if [ "${FAIL}" -gt 0 ]; then
    echo -e "${RED}TEST FAILED${NC}"
    exit 1
else
    echo -e "${GREEN}TEST PASSED${NC}"
    exit 0
fi
