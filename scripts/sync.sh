#!/usr/bin/env bash
# TSiSIP Sync (pull latest and update)
set -euo pipefail

echo "=== TSiSIP Sync ==="

# Pull latest
echo "Pulling latest changes..."
git pull origin main

# Update submodules (if any)
echo "Updating submodules..."
git submodule update --init --recursive 2>/dev/null || true

# Rebuild if needed
echo "Checking for changes..."
if git diff --name-only HEAD~1 | grep -q "Dockerfile\|docker-compose"; then
    echo "Docker changes detected, rebuilding..."
    make build
fi

# Restart if needed
if git diff --name-only HEAD~1 | grep -q "web/"; then
    echo "Web changes detected, restarting OCP..."
    docker compose restart ocp
fi

# Run migrations if needed
if git diff --name-only HEAD~1 | grep -q "db/init"; then
    echo "Database changes detected, running migrations..."
    bash scripts/migrate.sh
fi

echo ""
echo "=== Sync Complete ==="
