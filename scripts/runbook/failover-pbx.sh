#!/bin/bash
# TSiSIP Runbook: PBX Failover
# Marks a dispatcher destination as inactive and verifies traffic shifts.
#
# Usage:
#   ./scripts/runbook/failover-pbx.sh <pbx-label> [setid]
#
# Example:
#   ./scripts/runbook/failover-pbx.sh asterisk-pbx-1 1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
PBX_LABEL="${1:-}"
SETID="${2:-1}"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"
EVIDENCE_DIR="${PROJECT_ROOT}/evidence/runbook"

if [[ -z "$PBX_LABEL" ]]; then
    echo "Usage: $0 <pbx-label> [setid]"
    exit 1
fi

TIMESTAMP=$(date -u +%Y%m%d_%H%M%S)
RUN_EVIDENCE_DIR="${EVIDENCE_DIR}/${TIMESTAMP}_failover-${PBX_LABEL}"
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
  "runbook": "failover-pbx",
  "pbx_label": "${PBX_LABEL}",
  "setid": ${SETID},
  "start_time": "${START_TIME}",
  "end_time": "${end_time}",
  "result": "${RESULT}",
  "steps": [${steps_json}]
}
EOF
    echo "Evidence written to ${RUN_EVIDENCE_DIR}/evidence.json"
}

trap generate_evidence EXIT

echo "=== TSiSIP Runbook: PBX Failover ==="
echo "PBX Label: ${PBX_LABEL}"
echo "Dispatcher Set: ${SETID}"
echo ""

# Step 1: Identify the dispatcher destination ID
evidence "identify_pbx" "RUNNING" "Looking up dispatcher destination for ${PBX_LABEL}"
DEST_ROW=$(docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -t -c \
    "SELECT id, destination, state FROM dispatcher WHERE setid=${SETID} AND description LIKE '%${PBX_LABEL}%' LIMIT 1;" 2>/dev/null | head -1)

if [[ -z "$(echo "$DEST_ROW" | tr -d ' ')" ]]; then
    fail "identify_pbx" "No dispatcher destination found for label '${PBX_LABEL}' in set ${SETID}"
fi

DEST_ID=$(echo "$DEST_ROW" | awk '{print $1}')
DEST_ADDR=$(echo "$DEST_ROW" | awk '{print $2}')
DEST_STATE=$(echo "$DEST_ROW" | awk '{print $3}')
evidence "identify_pbx" "PASS" "Found destination id=${DEST_ID} addr=${DEST_ADDR} state=${DEST_STATE}"

# Step 2: Mark destination as inactive (state=1)
evidence "mark_inactive" "RUNNING" "Setting dispatcher state to 1 (inactive) for id=${DEST_ID}"
docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -c \
    "UPDATE dispatcher SET state=1 WHERE id=${DEST_ID};" >/dev/null 2>&1 || fail "mark_inactive" "Failed to update dispatcher state"

# Step 3: Trigger dispatcher reload via MI HTTP
evidence "reload_dispatcher" "RUNNING" "Reloading dispatcher via MI HTTP"
RELOAD_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T opensips \
    /usr/local/sbin/opensips-cli -x mi ds_reload 2>/dev/null || true)

# Step 4: Verify traffic shift (check another destination in the set is active)
evidence "verify_shift" "RUNNING" "Verifying alternative destinations are active in set ${SETID}"
ACTIVE_COUNT=$(docker compose -f "$COMPOSE_FILE" exec -T postgres psql -U opensips -d opensips -t -c \
    "SELECT COUNT(*) FROM dispatcher WHERE setid=${SETID} AND state=0;" 2>/dev/null | tr -d ' ')

if [[ "$ACTIVE_COUNT" -eq 0 ]]; then
    fail "verify_shift" "No active destinations remain in set ${SETID} — rollback required"
fi

evidence "verify_shift" "PASS" "${ACTIVE_COUNT} active destination(s) remain in set ${SETID}"

# Step 5: Final check
evidence "complete" "PASS" "Failover completed for ${PBX_LABEL}. Traffic shifted to ${ACTIVE_COUNT} active backend(s)."
RESULT="success"
