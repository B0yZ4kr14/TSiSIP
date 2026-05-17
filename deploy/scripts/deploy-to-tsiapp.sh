#!/bin/bash
# TSiSIP Production Deploy Script
# Deploys the full stack to TSiAPP VPS using Ansible

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENVIRONMENT="${1:-staging}"

echo "=== TSiSIP Deploy to TSiAPP ==="
echo "Environment: $ENVIRONMENT"
echo ""

# Step 1: Validate secrets
if [ ! -f /tmp/tsisip-secrets.* ]; then
    echo "[1/5] Discovering secrets..."
    "$SCRIPT_DIR/discover-and-secrets.sh" --check-only || {
        echo "ERROR: Secret validation failed. Run: $SCRIPT_DIR/discover-and-secrets.sh"
        exit 1
    }
else
    echo "[1/5] Secrets already validated"
fi

# Step 2: Validate configs
echo "[2/5] Validating configurations..."
cd "$SCRIPT_DIR/.."
./validate.sh || {
    echo "ERROR: Deployment validation failed"
    exit 1
}

# Step 3: Build images (if needed)
echo "[3/5] Building images..."
cd "$SCRIPT_DIR/../.."
docker compose build || {
    echo "ERROR: Docker build failed"
    exit 1
}

# Step 4: Run hardening (first deploy only)
read -p "Run server hardening playbook? (first deploy only) [y/N]: " RUN_HARDENING
if [ "$RUN_HARDENING" = "y" ] || [ "$RUN_HARDENING" = "Y" ]; then
    echo "[4/5] Running server hardening..."
    cd "$SCRIPT_DIR/../ansible"
    ansible-playbook -i inventory.yml playbook-hardening.yml
else
    echo "[4/5] Skipping hardening"
fi

# Step 5: Deploy
echo "[5/5] Deploying TSiSIP stack..."
cd "$SCRIPT_DIR/../ansible"
ansible-playbook -i inventory.yml playbook-deploy.yml

echo ""
echo "=== Deploy Complete ==="
echo "Verify at: https://tsiapp.io/TSiSIP"
echo "Health:  https://tsiapp.io/TSiSIP/health"
