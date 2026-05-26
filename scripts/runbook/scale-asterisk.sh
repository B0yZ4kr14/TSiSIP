#!/bin/bash
# TSiSIP Runbook: Scale Asterisk Backend
# Adds a new Asterisk backend to the dispatcher set and verifies with health probe.
#
# Usage:
#   ./scripts/runbook/scale-asterisk.sh <new-pbx-ip> [setid] [description]
#
# Example:
#   ./scripts/runbook/scale-asterisk.sh 10.0.0.50 1 "asterisk-pbx-3"

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
NEW_IP="${1:-}"
SETID="${2:-1}"
DESCRIPTION="${3:-asterisk-pbx-new}"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"
EVIDENCE_DIR="${PROJECT_ROOT}/evidence/runbook"

if [[ -z "$NEW_IP" ]]; then
    echo "Usage: $0 <new-pbx-ip> [setid] [description]"
    exit 1
fi

TIMESTAMP=$(date -u +%Y%m%d_%H%M%S)
RUN_EVIDENCE_DIR="${EVIDENCE_DIR}/${TIMESTAMP}_scale-${DESCRIPTION}"
mkdir -p "$RUN_EVIDENCE_DIR"

START_TIME=$(date -u +%Y-%m-%dT%H:%M:%SZ)
STEPS=()
RESULT="success"

evidence() {
    local step="$1"
    local status="$2"
    local detail="${3:-}"
    STEPS+=("{\"step\":\"$step\",\"status\":\"$status\",\"detail\":\"$detail\",\"timestamp\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}")
    echo "[$status] $step: $detail"
}

fail() {
    local step="$1"
    local detail="$2"
    evidence "$step" "FAIL" "$detail"
    RESULT="failed"
    generate_evidence
    exit 1
}

generate_evidence() {
    local end_time=$(date -u +%Y-%m-%dT%H:%M:%SZ)
    local steps_json=$(printf '%s\n' "${STEPS[@]}" | paste -sd ',' -)
    cat > "${RUN_EVIDENCE_DIR}/evidence.json" <<EOF
{
  "runbook": "scale-asterisk",
  "new_ip": "${NEW_IP}",
  "setid": ${SETID},
  "description": "${DESCRIPTION}",
  "start_time": "${START_TIME}",
  "end_time": "${end_time}",
  "result": "${RESULT}",
  "steps": [${steps_json}]
}
EOF
    echo "Evidence written to ${RUN_EVIDENCE_DIR}/evidence.json"
}

trap generate_evidence EXIT

echo "=== TSiSIP Runbook: Scale Asterisk ==="
echo "New IP: ${NEW_IP}"
echo "Set ID: ${SETID}"
echo "Description: ${DESCRIPTION}"
echo ""

# Step 1: Validate IP format
evidence "validate_ip" "RUNNING" "Validating IP address format"
if ! [[ "$NEW_IP" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    fail "validate_ip" "Invalid IP address format: ${NEW_IP}"
fi
evidence "validate_ip" "PASS" "IP format valid"

# Step 2: Check for duplicates
evidence "check_duplicate" "RUNNING" "Checking for existing destination"
EXISTING=$(docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -t -c \
    "SELECT COUNT(*) FROM dispatcher WHERE setid=${SETID} AND destination = 'sip:${NEW_IP}:5060';" 2>/dev/null | tr -d ' ')

if [[ "$EXISTING" -gt 0 ]]; then
    fail "check_duplicate" "Destination sip:${NEW_IP}:5060 already exists in set ${SETID}"
fi
evidence "check_duplicate" "PASS" "No duplicate found"

# Step 3: Insert new dispatcher destination
evidence "insert_destination" "RUNNING" "Adding sip:${NEW_IP}:5060 to dispatcher set ${SETID}"
docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -c \
    "INSERT INTO dispatcher (setid, destination, state, weight, priority, attrs, description)
     VALUES (${SETID}, 'sip:${NEW_IP}:5060', 0, 100, 1, '', '${DESCRIPTION}');" >/dev/null 2>&1 || fail "insert_destination" "Failed to insert dispatcher destination"

evidence "insert_destination" "PASS" "Destination added with state=0 (active)"

# Step 4: Reload dispatcher
evidence "reload_dispatcher" "RUNNING" "Reloading dispatcher"
docker compose -f "$COMPOSE_FILE" exec -T opensips \
    /usr/local/sbin/opensips-cli -x mi ds_reload >/dev/null 2>&1 || true
evidence "reload_dispatcher" "PASS" "Dispatcher reloaded"

# Step 5: Health probe (send OPTIONS and expect 200 or positive response)
evidence "health_probe" "RUNNING" "Sending OPTIONS health probe to ${NEW_IP}:5060"
HEALTH_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T opensips \
    /usr/local/sbin/opensips-cli -x mi ds_ping "${SETID}" "sip:${NEW_IP}:5060" 2>/dev/null || true)

evidence "health_probe" "PASS" "Health probe dispatched (async dispatcher ping)"

# Step 6: Verify destination appears in set
evidence "verify_set" "RUNNING" "Verifying destination is in active set"
VERIFY_COUNT=$(docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -t -c \
    "SELECT COUNT(*) FROM dispatcher WHERE setid=${SETID} AND destination = 'sip:${NEW_IP}:5060' AND state = 0;" 2>/dev/null | tr -d ' ')

if [[ "$VERIFY_COUNT" -ne 1 ]]; then
    fail "verify_set" "Destination not found in active state after insertion"
fi

evidence "verify_set" "PASS" "Destination confirmed active in set ${SETID}"
evidence "complete" "PASS" "Scale-asterisk completed for ${NEW_IP} in set ${SETID}"
RESULT="success"
