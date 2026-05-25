#!/bin/bash
# TSiSIP SIP Trunk Provider Integration Tests
# Feature 017 Wave 7: Testing & Validation

set -euo pipefail

echo "=== TSiSIP SIP Trunk Integration Tests ==="

PG_USER="${PG_USER:-opensips}"
PG_DB="${PG_DB:-opensips}"
PG_HOST="${PG_HOST:-postgres}"

# Skip if PostgreSQL is not reachable
if ! psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -c "SELECT 1" >/dev/null 2>&1; then
    echo "SKIP: PostgreSQL not reachable at $PG_HOST (trunk tests require running database)"
    exit 0
fi

FAIL=0

# a. Verify sip_trunk_providers table schema
echo "[TEST A] Verify sip_trunk_providers schema..."
if psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -c "SELECT id, name, host, port, transport, auth_username, auth_password_encrypted, from_domain, caller_id_prefix, priority, enabled FROM sip_trunk_providers LIMIT 0" >/dev/null 2>&1; then
    echo "PASS: sip_trunk_providers schema OK"
else
    echo "FAIL: sip_trunk_providers schema missing expected columns"
    FAIL=1
fi

# b. Verify sip_trunk_did_mappings schema and FKs
echo "[TEST B] Verify sip_trunk_did_mappings schema and foreign keys..."
if psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -c "SELECT id, trunk_provider_id, did_number, tenant_id, dispatcher_setid, destination, description, enabled FROM sip_trunk_did_mappings LIMIT 0" >/dev/null 2>&1; then
    FK_COUNT=$(psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -tc "SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_name='sip_trunk_did_mappings' AND constraint_type='FOREIGN KEY'" | tr -d '[:space:]') || true
    if [ "${FK_COUNT:-0}" -ge 2 ]; then
        echo "PASS: sip_trunk_did_mappings schema and FKs OK (${FK_COUNT} FKs)"
    else
        echo "FAIL: sip_trunk_did_mappings expected >=2 FKs, found ${FK_COUNT:-0}"
        FAIL=1
    fi
else
    echo "FAIL: sip_trunk_did_mappings schema missing"
    FAIL=1
fi

# c. Verify dispatcher sync trigger populated setid 100
echo "[TEST C] Verify dispatcher sync trigger (setid 100)..."
psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -c "INSERT INTO sip_trunk_providers (name, host, port, priority, enabled) VALUES ('__TestProbe__', '127.0.0.1', 5060, 99, true) ON CONFLICT (name) DO UPDATE SET enabled = true, host = '127.0.0.1', port = 5060, priority = 99" >/dev/null 2>&1 || true
DS_COUNT=$(psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -tc "SELECT COUNT(*) FROM dispatcher WHERE setid = 100 AND description = 'Trunk: __TestProbe__'" | tr -d '[:space:]') || true
psql -U "$PG_USER" -d "$PG_DB" -h "$PG_HOST" -c "DELETE FROM sip_trunk_providers WHERE name = '__TestProbe__'" >/dev/null 2>&1 || true
if [ "${DS_COUNT:-0}" -ge 1 ]; then
    echo "PASS: Dispatcher sync trigger inserted setid=100 row"
else
    echo "FAIL: Dispatcher sync trigger did not populate setid=100"
    FAIL=1
fi

# d. Verify OpenSIPS config loads with trunk modules (opensips -c)
echo "[TEST D] Verify OpenSIPS config syntax and trunk modules..."
CFG_OK=0
if command -v opensips >/dev/null 2>&1; then
    if opensips -c -f /etc/opensips/opensips.cfg >/dev/null 2>&1; then
        CFG_OK=1
    fi
elif command -v docker >/dev/null 2>&1 && docker image inspect tsisip-opensips:latest >/dev/null 2>&1; then
    if docker run --rm \
        -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
        -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
        -e RTPENGINE_HOST=rtpengine \
        -v "$(pwd)/secrets/db_password:/run/secrets/db_password:ro" \
        -v "$(pwd)/secrets/auth_secret:/run/secrets/auth_secret:ro" \
        -v "$(pwd)/secrets/topology_secret:/run/secrets/topology_secret:ro" \
        tsisip-opensips:latest \
        /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg >/dev/null 2>&1; then
        CFG_OK=1
    fi
fi

if [ "$CFG_OK" -eq 1 ]; then
    echo "PASS: OpenSIPS config syntax OK"
else
    echo "FAIL: OpenSIPS config check failed or not available"
    FAIL=1
fi

# e. Send test OPTIONS to localhost:5060 and verify 200 OK
echo "[TEST E] Send OPTIONS probe to localhost:5060..."
OPTIONS_OK=0
if command -v python3 >/dev/null 2>&1; then
    RESP=$(python3 -c "
import socket
msg = b'OPTIONS sip:localhost:5060 SIP/2.0\r\nVia: SIP/2.0/UDP 127.0.0.1:5061;branch=z9hG4bK-test\r\nFrom: <sip:test@localhost>;tag=test\r\nTo: <sip:localhost:5060>\r\nCall-ID: test-options-001@127.0.0.1\r\nCSeq: 1 OPTIONS\r\nMax-Forwards: 70\r\nContent-Length: 0\r\n\r\n'
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
try:
    sock.sendto(msg, ('127.0.0.1', 5060))
    data, _ = sock.recvfrom(4096)
    print(data.decode())
except Exception as e:
    print('ERROR:', e)
" 2>/dev/null) || true
    if echo "$RESP" | grep -q 'SIP/2.0 200 OK'; then
        OPTIONS_OK=1
    fi
fi

if [ "$OPTIONS_OK" -eq 1 ]; then
    echo "PASS: OPTIONS probe returned 200 OK"
else
    echo "FAIL: OPTIONS probe did not return 200 OK"
    FAIL=1
fi

# f. Verify trunk routing route exists in config
echo "[TEST F] Verify trunk routing routes in OpenSIPS config..."
ROUTE_OK=0
if [ -f /etc/opensips/opensips.cfg ]; then
    if grep -q 'route\[TRUNK_ROUTING\]' /etc/opensips/opensips.cfg && grep -q 'route\[INBOUND_DID_ROUTING\]' /etc/opensips/opensips.cfg; then
        ROUTE_OK=1
    fi
elif [ -f opensips/opensips.cfg.tpl ]; then
    if grep -q 'route\[TRUNK_ROUTING\]' opensips/opensips.cfg.tpl && grep -q 'route\[INBOUND_DID_ROUTING\]' opensips/opensips.cfg.tpl; then
        ROUTE_OK=1
    fi
fi

if [ "$ROUTE_OK" -eq 1 ]; then
    echo "PASS: Trunk routing routes found in config"
else
    echo "FAIL: Trunk routing routes missing from config"
    FAIL=1
fi

echo ""
if [ "$FAIL" -eq 0 ]; then
    echo "=== ALL TRUNK TESTS PASSED ==="
    exit 0
else
    echo "=== SOME TRUNK TESTS FAILED ==="
    exit 1
fi
