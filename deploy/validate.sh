#!/bin/bash
# TSiSIP Deployment Validation Script
# Runs all checks: secrets, Ansible syntax, Nginx config, audit completeness

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PASS=0
FAIL=0

pass() { echo -e "${GREEN}[PASS]${NC} $*"; ((PASS++)) || true; }
fail() { echo -e "${RED}[FAIL]${NC} $*"; ((FAIL++)) || true; }
info() { echo -e "${YELLOW}[INFO]${NC} $*"; }

info "=== TSiSIP Deployment Validation ==="

# 1. Secret discovery script exists and is executable
if [ -x "$SCRIPT_DIR/scripts/discover-and-secrets.sh" ]; then
    pass "discover-and-secrets.sh is executable"
else
    fail "discover-and-secrets.sh not found or not executable"
fi

# 2. Secret discovery syntax check
if bash -n "$SCRIPT_DIR/scripts/discover-and-secrets.sh" 2>/dev/null; then
    pass "discover-and-secrets.sh syntax valid"
else
    fail "discover-and-secrets.sh syntax error"
fi

# 3. GitHub init script exists and is executable
if [ -x "$SCRIPT_DIR/scripts/github-init-repo.sh" ]; then
    pass "github-init-repo.sh is executable"
else
    fail "github-init-repo.sh not found or not executable"
fi

# 4. GitHub init syntax check
if bash -n "$SCRIPT_DIR/scripts/github-init-repo.sh" 2>/dev/null; then
    pass "github-init-repo.sh syntax valid"
else
    fail "github-init-repo.sh syntax error"
fi

# 5. Ansible inventory exists
if [ -f "$SCRIPT_DIR/ansible/inventory.yml" ]; then
    pass "ansible/inventory.yml exists"
else
    fail "ansible/inventory.yml missing"
fi

# 6. Ansible playbook exists
if [ -f "$SCRIPT_DIR/ansible/playbook-deploy.yml" ]; then
    pass "ansible/playbook-deploy.yml exists"
else
    fail "ansible/playbook-deploy.yml missing"
fi

# 7. Ansible hardening playbook exists
if [ -f "$SCRIPT_DIR/ansible/playbook-hardening.yml" ]; then
    pass "ansible/playbook-hardening.yml exists"
else
    fail "ansible/playbook-hardening.yml missing"
fi

# 8. Ansible syntax check (SG2.3)
info "=== Ansible Syntax Check ==="
if command -v ansible-playbook >/dev/null 2>&1; then
    if ansible-playbook --syntax-check -i "$SCRIPT_DIR/ansible/inventory.yml" "$SCRIPT_DIR/ansible/playbook-deploy.yml" >/dev/null 2>&1; then
        pass "ansible playbook-deploy.yml syntax valid (host binary)"
    else
        fail "ansible playbook-deploy.yml syntax error"
    fi
    
    if ansible-playbook --syntax-check -i "$SCRIPT_DIR/ansible/inventory.yml" "$SCRIPT_DIR/ansible/playbook-hardening.yml" >/dev/null 2>&1; then
        pass "ansible playbook-hardening.yml syntax valid (host binary)"
    else
        fail "ansible playbook-hardening.yml syntax error"
    fi
elif [ -n "${CI:-}" ] || [ -n "${GITHUB_ACTIONS:-}" ]; then
    # CI-native check using Docker
    if docker run --rm -v "$SCRIPT_DIR/ansible:/ansible:ro" \
        cytopia/ansible:latest-tools ansible-playbook --syntax-check \
        -i /ansible/inventory.yml /ansible/playbook-deploy.yml >/dev/null 2>&1; then
        pass "ansible playbook-deploy.yml syntax valid (container CI)"
    else
        fail "ansible playbook-deploy.yml syntax error (container CI)"
    fi
    
    if docker run --rm -v "$SCRIPT_DIR/ansible:/ansible:ro" \
        cytopia/ansible:latest-tools ansible-playbook --syntax-check \
        -i /ansible/inventory.yml /ansible/playbook-hardening.yml >/dev/null 2>&1; then
        pass "ansible playbook-hardening.yml syntax valid (container CI)"
    else
        fail "ansible playbook-hardening.yml syntax error (container CI)"
    fi
else
    info "ansible-playbook not available and not in CI, skipping syntax checks"
fi

# 9. Nginx config exists
if [ -f "$SCRIPT_DIR/nginx/tsisip-reverse-proxy.conf" ]; then
    pass "nginx/tsisip-reverse-proxy.conf exists"
else
    fail "nginx/tsisip-reverse-proxy.conf missing"
fi

# 10. Nginx syntax check (SG2.2)
info "=== Nginx Syntax Check ==="
# Create dummy certificates and a copy of nginx config with adjusted SSL paths
TEMP_CERTS=$(mktemp -d)
openssl req -x509 -nodes -days 1 -newkey rsa:2048 \
    -keyout "$TEMP_CERTS/tsiapp.io.key" \
    -out "$TEMP_CERTS/tsiapp.io.crt" \
    -subj "/CN=tsiapp.io" 2>/dev/null || true
TEMP_NGINX=$(mktemp -d)
# Copy config and replace SSL paths with dummy cert paths
sed "s|/etc/ssl/certs/tsiapp.io.crt|$TEMP_CERTS/tsiapp.io.crt|g; s|/etc/ssl/private/tsiapp.io.key|$TEMP_CERTS/tsiapp.io.key|g" \
    "$SCRIPT_DIR/nginx/tsisip-reverse-proxy.conf" > "$TEMP_NGINX/tsisip-reverse-proxy.conf"
cat > "$TEMP_NGINX/nginx.conf" << EOF
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;
events { worker_connections 1024; }
http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    access_log /var/log/nginx/access.log;
    include $TEMP_NGINX/tsisip-reverse-proxy.conf;
}
EOF

if command -v nginx >/dev/null 2>&1; then
    if nginx -t -c "$TEMP_NGINX/nginx.conf" 2>&1; then
        pass "nginx config syntax valid (host binary)"
    else
        fail "nginx config syntax error"
    fi
elif [ -n "${CI:-}" ] || [ -n "${GITHUB_ACTIONS:-}" ]; then
    # CI-native check using nginx container with dummy certificates
    if docker run --rm \
        -v "$TEMP_NGINX:/etc/nginx:ro" \
        -v "$TEMP_CERTS:$TEMP_CERTS:ro" \
        nginx:alpine \
        nginx -t >/dev/null 2>&1; then
        pass "nginx config syntax valid (container CI)"
    else
        fail "nginx config syntax error (container CI)"
    fi
else
    info "nginx not available and not in CI, skipping syntax check"
fi
rm -rf "$TEMP_CERTS" "$TEMP_NGINX"

# 11. Audit document exists
if [ -f "$SCRIPT_DIR/audit/DEVSECOPS-AUDIT.md" ]; then
    pass "audit/DEVSECOPS-AUDIT.md exists"
else
    fail "audit/DEVSECOPS-AUDIT.md missing"
fi

# 12. Audit document completeness
SPoF_COUNT=$(grep -c "SPoF" "$SCRIPT_DIR/audit/DEVSECOPS-AUDIT.md" 2>/dev/null || echo 0)
if [ "$SPoF_COUNT" -ge 5 ]; then
    pass "Audit document covers at least 5 SPoF scenarios ($SPoF_COUNT found)"
else
    fail "Audit document incomplete ($SPoF_COUNT SPoF found, expected >= 5)"
fi

# 13. SPoF test scripts exist
SPoF_TESTS=$(find "$SCRIPT_DIR/audit/tests" -name "test-spof-*.sh" 2>/dev/null | wc -l)
if [ "$SPoF_TESTS" -ge 3 ]; then
    pass "SPoF test scripts present ($SPoF_TESTS found)"
else
    fail "SPoF test scripts missing ($SPoF_TESTS found, expected >= 3)"
fi

# 14. Makefile exists
if [ -f "$SCRIPT_DIR/Makefile" ]; then
    pass "Makefile exists"
else
    fail "Makefile missing"
fi

# 15. Security verification scripts exist (SG3.x)
info "=== Security Verification Scripts ==="
for script in verify-network-isolation.sh verify-secrets-audit.sh verify-nginx-tls.sh verify-health-checks.sh; do
    if [ -x "$SCRIPT_DIR/../scripts/$script" ]; then
        pass "$script exists and is executable"
    else
        fail "$script missing or not executable"
    fi
done

# Summary
echo ""
echo "================================"
echo "Validation Summary: $PASS passed, $FAIL failed"
echo "================================"

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}All checks passed!${NC}"
    exit 0
else
    echo -e "${RED}Some checks failed.${NC}"
    exit 1
fi
