#!/usr/bin/env bash
# TSiSIP Update Script
set -euo pipefail

echo "=== TSiSIP Updater ==="

# Backup first
echo "Creating backup..."
./scripts/backup-db.sh

# Pull latest
echo "Pulling latest changes..."
git pull origin main

# Rebuild
echo "Rebuilding images..."
docker compose build

# Run migrations
echo "Running migrations..."
for f in db/init/*.sql; do
    docker compose exec -T postgres psql -U opensips -d opensips -f "/docker-entrypoint-initdb.d/$(basename "$f")" 2>/dev/null || true
done

# Restart
echo "Restarting services..."
docker compose up -d

# Verify
echo "Verifying..."
if curl -fsSL http://localhost/health.php >/dev/null 2>&1; then
    echo "✓ Update successful!"
else
    echo "⚠ Health check failed. Check logs: docker compose logs"
fi

echo "=== Update Complete ==="
