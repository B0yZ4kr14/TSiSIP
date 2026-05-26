#!/usr/bin/env bash
# Feature 017 AC4: Outbound trunk routing end-to-end test wrapper.
# Sets up mock trunk, restarts OpenSIPS, and runs Python test inside sip_edge.
set -euo pipefail

NETWORK="tsisip_sip_edge"
MOCK_CONTAINER="tsisip-mock-trunk"
POSTGRES_CONTAINER="tsisip-postgres-1"
OPENSIPS_CONTAINER="tsisip-opensips-1"
MOCK_TRUNK_NAME="mock-outbound-e2e"

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

pass() { echo -e "${GREEN}[PASS]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

cleanup() {
    docker rm -f "${MOCK_CONTAINER}" >/dev/null 2>&1 || true
    docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips \
        -c "DELETE FROM sip_trunk_providers WHERE name = '${MOCK_TRUNK_NAME}';" >/dev/null 2>&1 || true
}
trap cleanup EXIT

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=== Starting mock trunk ==="
docker run -d --rm --name "${MOCK_CONTAINER}" --network "${NETWORK}" --cap-drop ALL \
    tsisip/mock-trunk:latest >/dev/null 2>&1 || fail "Failed to start mock trunk"
sleep 2
MOCK_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "${MOCK_CONTAINER}")
echo "Mock trunk IP: ${MOCK_IP}"

echo "=== Inserting mock outbound trunk ==="
docker exec "${POSTGRES_CONTAINER}" psql -U opensips -d opensips -c \
"INSERT INTO sip_trunk_providers (name, host, port, transport, priority, enabled, registration_required, max_cps, max_concurrent)
VALUES ('${MOCK_TRUNK_NAME}', '${MOCK_IP}', 5060, 'udp', 0, true, false, 100, 1000);" >/dev/null 2>&1 || fail "Failed to insert mock trunk"

echo "=== Restarting OpenSIPS to load dispatcher ==="
docker compose -f /opt/tsisip/docker-compose.vps.yml restart opensips >/dev/null 2>&1 || true
sleep 8

echo "=== Running end-to-end SIP test ==="
docker run --rm --network "${NETWORK}" -v "${SCRIPT_DIR}/test_trunk_outbound_call.py:/test.py" \
    python:3.11-alpine python3 /test.py

pass "AC4 outbound trunk routing validated"
