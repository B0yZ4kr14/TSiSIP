#!/usr/bin/env bash
# TSiSIP Seed Data
set -euo pipefail

echo "=== TSiSIP Seed Data ==="

# Seed database
docker compose exec -T postgres psql -U opensips -d opensips -f /docker-entrypoint-initdb.d/03-seed-data.sql

echo "=== Seed Complete ==="
