#!/usr/bin/env bash
# TSiSIP Size Check
set -euo pipefail

echo "=== TSiSIP Size Check ==="

# Git repo
echo ""
echo "Git repo:"
du -sh .git

# Code
echo ""
echo "Code:"
du -sh web/

# Docs
echo ""
echo "Docs:"
du -sh docs/

# Tests
echo ""
echo "Tests:"
du -sh tests/

# Scripts
echo ""
echo "Scripts:"
du -sh scripts/

# Docker
echo ""
echo "Docker images:"
docker images --format "table {{.Repository}}\t{{.Size}}" 2>/dev/null | grep tsisip || echo "No TSiSIP images"

# Containers
echo ""
echo "Container sizes:"
docker ps --size --format "table {{.Names}}\t{{.Size}}" 2>/dev/null | grep tsisip || echo "No running containers"

# Database
echo ""
echo "Database:"
docker compose exec -T postgres psql -U opensips -d opensips -c "
SELECT pg_size_pretty(pg_database_size('opensips'));
" 2>/dev/null || echo "Database not available"

# Largest files
echo ""
echo "Top 10 largest files:"
find web -type f -exec du -h {} + | sort -rh | head -10

echo ""
echo "=== Size Check Complete ==="
