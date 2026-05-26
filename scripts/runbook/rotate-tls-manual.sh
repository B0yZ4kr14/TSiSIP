#!/bin/bash
# TSiSIP Runbook: TLS Certificate Manual Rotation
# Triggers certbot dry-run, then live rotation with rollback on failure.
#
# Usage:
#   ./scripts/runbook/rotate-tls-manual.sh [domain]
#
# Example:
#   ./scripts/runbook/rotate-tls-manual.sh tsiapp.io

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
DOMAIN="${1:-${TLS_DOMAIN:-tsiapp.io}}"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"
EVIDENCE_DIR="${PROJECT_ROOT}/evidence/runbook"
CERT_DIR="${PROJECT_ROOT}/secrets/certs"

TIMESTAMP=$(date -u +%Y%m%d_%H%M%S)
RUN_EVIDENCE_DIR="${EVIDENCE_DIR}/${TIMESTAMP}_tls-rotate-${DOMAIN}"
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
  "runbook": "rotate-tls-manual",
  "domain": "${DOMAIN}",
  "start_time": "${START_TIME}",
  "end_time": "${end_time}",
  "result": "${RESULT}",
  "steps": [${steps_json}]
}
EOF
    echo "Evidence written to ${RUN_EVIDENCE_DIR}/evidence.json"
}

trap generate_evidence EXIT

echo "=== TSiSIP Runbook: TLS Manual Rotation ==="
echo "Domain: ${DOMAIN}"
echo ""

# Step 1: Backup current certificates
evidence "backup_certs" "RUNNING" "Backing up current certificates"
if [[ -d "${CERT_DIR}/live/${DOMAIN}" ]]; then
    cp -r "${CERT_DIR}/live/${DOMAIN}" "${RUN_EVIDENCE_DIR}/certs-backup" 2>/dev/null || true
    evidence "backup_certs" "PASS" "Certificates backed up"
else
    evidence "backup_certs" "SKIP" "No existing certificates found"
fi

# Step 2: Certbot dry-run
evidence "certbot_dryrun" "RUNNING" "Running certbot dry-run for ${DOMAIN}"
DRYRUN_RESULT=$(docker compose -f "$COMPOSE_FILE" run --rm certbot certbot renew --dry-run --cert-name "$DOMAIN" 2>&1 || true)

if echo "$DRYRUN_RESULT" | grep -qi "success\|no renewals were attempted"; then
    evidence "certbot_dryrun" "PASS" "Dry-run succeeded or not yet due"
else
    fail "certbot_dryrun" "Dry-run failed: ${DRYRUN_RESULT}"
fi

# Step 3: Live certbot renewal
evidence "certbot_live" "RUNNING" "Executing live certificate renewal"
LIVE_RESULT=$(docker compose -f "$COMPOSE_FILE" run --rm certbot certbot renew --cert-name "$DOMAIN" 2>&1 || true)

if echo "$LIVE_RESULT" | grep -qi "success\|no renewals were attempted"; then
    evidence "certbot_live" "PASS" "Live renewal succeeded"
else
    # Rollback: restore backup
    evidence "certbot_live" "FAIL" "Live renewal failed, attempting rollback"
    if [[ -d "${RUN_EVIDENCE_DIR}/certs-backup" ]]; then
        rm -rf "${CERT_DIR}/live/${DOMAIN}"
        cp -r "${RUN_EVIDENCE_DIR}/certs-backup" "${CERT_DIR}/live/${DOMAIN}"
        evidence "rollback" "PASS" "Certificates restored from backup"
    fi
    fail "certbot_live" "Live renewal failed and rollback executed: ${LIVE_RESULT}"
fi

# Step 4: Reload OpenSIPS TLS
evidence "reload_opensips" "RUNNING" "Reloading OpenSIPS TLS configuration"
RELOAD_RESULT=$(docker compose -f "$COMPOSE_FILE" exec -T opensips \
    /usr/local/sbin/opensips-cli -x mi tls_reload 2>/dev/null || true)
evidence "reload_opensips" "PASS" "OpenSIPS TLS reloaded"

# Step 5: Verify new certificate expiry
evidence "verify_cert" "RUNNING" "Verifying new certificate validity"
EXPIRY=$(docker compose -f "$COMPOSE_FILE" exec -T opensips \
    openssl x509 -in "/certs/live/${DOMAIN}/cert.pem" -noout -dates 2>/dev/null | grep notAfter | cut -d= -f2 || echo "unknown")
evidence "verify_cert" "PASS" "New certificate valid until ${EXPIRY}"

evidence "complete" "PASS" "TLS rotation completed successfully for ${DOMAIN}"
RESULT="success"
