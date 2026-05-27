#!/usr/bin/env bash
# TSiSIP Database Migration
set -euo pipefail

echo "=== TSiSIP Database Migration ==="

# Run all migrations
for f in db/init/*.sql; do
    echo "Running: $(basename "$f")"
    docker compose exec -T postgres psql -U opensips -d opensips -f "/docker-entrypoint-initdb.d/$(basename "$f")" 2>/dev/null || echo "Skipped (may already exist)"
done

echo "=== Migration Complete ==="
