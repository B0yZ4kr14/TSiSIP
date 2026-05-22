#!/bin/bash
# Diagnose opensips startup issues on VPS
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "=== OpenSIPS Diagnosis ==="
echo ""

# Check if secrets exist
echo "[CHECK] Secrets..."
for secret in db_password auth_secret topology_secret ca.crt server.crt server.key crl.pem; do
    if [ -f "$PROJECT_ROOT/secrets/$secret" ]; then
        echo "  OK: $secret"
    else
        echo "  MISSING: $secret"
    fi
done

# Check if template exists
echo ""
echo "[CHECK] Config template..."
if [ -f "$PROJECT_ROOT/opensips/opensips.cfg.tpl" ]; then
    echo "  OK: opensips.cfg.tpl exists"
    # Check for required variables in template
    for var in OPENSIPS_LISTEN_IP HOST_PUBLIC_IP DB_HOST DB_NAME DB_USER DB_PASSWORD AUTH_SECRET_32_CHARS TOPOLOGY_SECRET RTPENGINE_HOST; do
        if grep -q "\${$var}" "$PROJECT_ROOT/opensips/opensips.cfg.tpl"; then
            echo "  VAR: $var referenced"
        fi
    done
else
    echo "  MISSING: opensips.cfg.tpl"
fi

# Check envsubst availability in image
echo ""
echo "[CHECK] envsubst in image..."
docker run --rm tsisip/opensips:test which envsubst 2>/dev/null && echo "  OK: envsubst available" || echo "  MISSING: envsubst not in image"

echo ""
echo "=== Run manual test ==="
echo "docker run --rm --network tsisip_sip_edge --network tsisip_sip_internal --network tsisip_db_internal \\"
echo "  -e OPENSIPS_LISTEN_IP=0.0.0.0 -e HOST_PUBLIC_IP=127.0.0.1 \\"
echo "  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \\"
echo "  -v $(pwd)/opensips/opensips.cfg.tpl:/etc/opensips/opensips.cfg.tpl:ro \\"
echo "  tsisip/opensips:test /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg"
