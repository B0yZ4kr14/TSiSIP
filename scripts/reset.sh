#!/usr/bin/env bash
# TSiSIP Reset (DANGER: Clears all data)
set -euo pipefail

echo "WARNING: This will delete all data!"
read -p "Are you sure? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Cancelled"
    exit 0
fi

echo "=== TSiSIP Reset ==="

# Stop and remove volumes
docker compose down -v

# Start fresh
docker compose up -d

# Wait for DB
sleep 5

# Run migrations
bash scripts/migrate.sh

# Seed data
bash scripts/seed.sh

echo "=== Reset Complete ==="
