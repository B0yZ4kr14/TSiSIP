#!/usr/bin/env bash
# Feature 017 AC3: Inbound DID routing end-to-end test wrapper.
set -euo pipefail

NETWORK="tsisip_sip_edge"
MOCK_CONTAINER="tsisip-mock-trunk"
POSTGRES_CONTAINER="tsisip-postgres-1"
MOCK_TRUNK_NAME="mock-inbound-e2e"

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

pass() { echo -e "${GREEN}[PASS]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

cleanup() {
    docker rm -f "${MOCK_CONTAINER}" >/dev/null 2>&1 || true
    docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips \
        -c "DELETE FROM sip_trunk_providers WHERE name = '${MOCK_TRUNK_NAME}';" >/dev/null 2>&1 || true
    docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips \
        -c "DELETE FROM sip_trunk_did_mappings WHERE did_number = '+19998887777';" >/dev/null 2>&1 || true
}
trap cleanup EXIT

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Starting mock trunk ==="
docker run -d --rm --name "${MOCK_CONTAINER}" --network "${NETWORK}" --cap-drop ALL \
    tsisip/mock-trunk:latest >/dev/null 2>&1 || fail "Failed to start mock trunk"
sleep 2
MOCK_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "${MOCK_CONTAINER}")
echo "Mock trunk IP: ${MOCK_IP}"

echo "=== Inserting mock inbound trunk and DID mapping ==="
docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -c \
"INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, registration_required, max_cps, max_concurrent)
VALUES ('${MOCK_TRUNK_NAME}', '${MOCK_IP}', 5060, 'udp', 1, true, false, 100, 1000);" >/dev/null 2>&1 || fail "Failed to insert mock trunk"

MOCK_TRUNK_ID=$(docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -Atc \
    "SELECT id FROM sip_trunk_providers WHERE name = '${MOCK_TRUNK_NAME}';")

docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -c \
"INSERT INTO sip_trunk_did_mappings (did_number, tenant_id, trunk_provider_id, dispatcher_setid, enabled)
VALUES ('+19998887777', '00000000-0000-0000-0000-000000000000', ${MOCK_TRUNK_ID}, 1, true);" >/dev/null 2>&1 || fail "Failed to insert DID mapping"

echo "=== Running inbound DID test from mock trunk container ==="
docker cp "${SCRIPT_DIR}/test_trunk_inbound_call.py" "${MOCK_CONTAINER}:/test.py"
docker exec "${MOCK_CONTAINER}" python3 /test.py

pass "AC3 inbound DID routing validated"
