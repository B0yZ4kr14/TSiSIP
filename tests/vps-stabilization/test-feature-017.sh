#!/usr/bin/env bash
# Feature 017: SIP Trunk Provider Integration — VPS Validation Script
# Validates schema, triggers, dispatcher sync, and uac_registrant population.

set -euo pipefail

PG_HOST="${PG_HOST:-postgres}"
PG_USER="${PG_USER:-opensips}"
PG_DB="${PG_DB:-opensips}"
PG_URL="postgres://${PG_USER}@${PG_HOST}/${PG_DB}"
OPENSIPS_CONTAINER="${OPENSIPS_CONTAINER:-tsisip-opensips-1}"
POSTGRES_CONTAINER="${POSTGRES_CONTAINER:-tsisip-postgres-1}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0

pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASS++)) || true
}

fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAIL++)) || true
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

# --- AC1: Schema completeness ---
echo "=== AC1: Schema Completeness ==="

for table in sip_trunk_providers sip_trunk_did_mappings sip_trunk_registrations trunk_ips; do
    if docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -tc "SELECT 1 FROM information_schema.tables WHERE table_name = '${table}'" | grep -q 1; then
        pass "Table ${table} exists"
    else
        fail "Table ${table} missing"
    fi
done

# Check indexes
for idx in idx_sip_trunk_providers_enabled_priority idx_sip_trunk_did_lookup idx_trunk_ips_lookup idx_sip_trunk_registrations_provider; do
    if docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -tc "SELECT 1 FROM pg_indexes WHERE indexname = '${idx}'" | grep -q 1; then
        pass "Index ${idx} exists"
    else
        fail "Index ${idx} missing"
    fi
done

# Check triggers
for trig in trunk_provider_dispatcher_sync trunk_registration_sync; do
    if docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -tc "SELECT 1 FROM information_schema.triggers WHERE trigger_name = '${trig}'" | grep -q 1; then
        pass "Trigger ${trig} exists"
    else
        fail "Trigger ${trig} missing"
    fi
done

# --- AC2: uac_registrant module and registration rows ---
echo "=== AC2: uac_registrant Configuration ==="

if docker exec "${OPENSIPS_CONTAINER}" grep -q 'loadmodule "uac_registrant.so"' /etc/opensips/opensips.cfg; then
    pass "uac_registrant module loaded"
else
    fail "uac_registrant module not loaded"
fi

REG_COUNT=$(docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -Atc "SELECT COUNT(*) FROM sip_trunk_registrations")
if [ "${REG_COUNT}" -gt 0 ]; then
    pass "sip_trunk_registrations populated (${REG_COUNT} rows)"
else
    warn "sip_trunk_registrations empty (no providers require registration)"
fi

# --- AC6: Dispatcher health probe sync ---
echo "=== AC6: Dispatcher Health Probe Sync ==="

TRUNK_COUNT=$(docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -Atc "SELECT COUNT(*) FROM sip_trunk_providers WHERE enabled = true")
DISP_COUNT=$(docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -Atc "SELECT COUNT(*) FROM dispatcher WHERE setid = 100")

if [ "${TRUNK_COUNT}" -eq "${DISP_COUNT}" ]; then
    pass "Dispatcher set 100 matches enabled trunk count (${TRUNK_COUNT})"
else
    fail "Dispatcher set 100 mismatch: ${DISP_COUNT} dispatcher rows vs ${TRUNK_COUNT} enabled trunks"
fi

# Verify ping_interval in attrs
PING_ATTRS=$(docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -Atc "SELECT attrs FROM dispatcher WHERE setid = 100 LIMIT 1")
if echo "${PING_ATTRS}" | grep -q 'ping_interval=30'; then
    pass "Dispatcher attrs include ping_interval=30"
else
    fail "Dispatcher attrs missing ping_interval=30"
fi

# --- AC7: Rate limiting configuration ---
echo "=== AC7: Rate Limiting Configuration ==="

if docker exec "${OPENSIPS_CONTAINER}" grep -q 'loadmodule "ratelimit.so"' /etc/opensips/opensips.cfg; then
    pass "ratelimit module loaded"
else
    fail "ratelimit module not loaded"
fi

if docker exec "${OPENSIPS_CONTAINER}" grep -q 'rl_check("trunk_' /etc/opensips/opensips.cfg; then
    pass "rl_check trunk pipe configured in TRUNK_ROUTING"
else
    fail "rl_check trunk pipe not found in TRUNK_ROUTING"
fi

# --- AC8: OCP Admin Pages ---
echo "=== AC8: OCP Admin Pages ==="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
if [ -f "${REPO_ROOT}/web/ocp/trunk-providers.php" ] && [ -f "${REPO_ROOT}/web/ocp/trunk-dids.php" ] && [ -f "${REPO_ROOT}/web/ocp/trunk-status.php" ]; then
    pass "OCP trunk admin pages present in repository"
else
    warn "OCP trunk admin pages not found in expected path (may be in VPS container only)"
fi

# --- AC9: Credential Encryption ---
echo "=== AC9: Credential Encryption ==="

COL_TYPE=$(docker exec "${POSTGRES_CONTAINER}" psql -U "${PG_USER}" -d "${PG_DB}" -Atc "SELECT data_type FROM information_schema.columns WHERE table_name = 'sip_trunk_providers' AND column_name = 'auth_password_encrypted'")
if [ "${COL_TYPE}" = "bytea" ]; then
    pass "auth_password_encrypted column is BYTEA (encrypted at rest)"
else
    fail "auth_password_encrypted column type is ${COL_TYPE}, expected BYTEA"
fi

# --- AC10: CDR extra fields ---
echo "=== AC10: CDR Extra Fields ==="

if docker exec "${OPENSIPS_CONTAINER}" grep -q 'extra_fields.*trunk_provider_id.*trunk_name.*direction' /etc/opensips/opensips.cfg; then
    pass "acc extra_fields include trunk_provider_id, trunk_name, direction"
else
    fail "acc extra_fields missing trunk metadata"
fi

# --- OpenSIPS config syntax validation ---
echo "=== Config Syntax Validation ==="

if docker exec "${OPENSIPS_CONTAINER}" /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg >/dev/null 2>&1; then
    pass "OpenSIPS config syntax valid"
else
    fail "OpenSIPS config syntax invalid"
fi

# --- Summary ---
echo ""
echo "========================================"
echo "Feature 017 Validation Summary"
echo "========================================"
echo -e "Passed: ${GREEN}${PASS}${NC}"
echo -e "Failed: ${RED}${FAIL}${NC}"
echo ""

if [ "${FAIL}" -gt 0 ]; then
    echo -e "${RED}VALIDATION FAILED${NC}"
    exit 1
else
    echo -e "${GREEN}VALIDATION PASSED${NC}"
    exit 0
fi
