#!/usr/bin/env bash
# TSiSIP Docker Cleanup
set -euo pipefail

echo "=== TSiSIP Docker Cleanup ==="

# Stop services
echo "Stopping services..."
docker compose down 2>/dev/null || true

# Remove unused containers
echo "Removing unused containers..."
docker container prune -f

# Remove unused images
echo "Removing unused images..."
docker image prune -f

# Remove unused volumes
echo "Removing unused volumes..."
docker volume prune -f

# Remove unused networks
echo "Removing unused networks..."
docker network prune -f

# System prune
echo "System prune..."
docker system prune -f

echo "=== Cleanup Complete ==="
