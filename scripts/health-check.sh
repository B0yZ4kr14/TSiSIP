#!/usr/bin/env bash
# TSiSIP Health Check
set -euo pipefail

URL="${1:-http://localhost}"

echo "=== TSiSIP Health Check ==="
echo "URL: $URL"
echo "Time: $(date)"

# Health endpoint
echo ""
echo "1. Health endpoint:"
curl -fsSL "$URL/health.php" 2>/dev/null | python3 -m json.tool 2>/dev/null || echo "Failed"

# Login page
echo ""
echo "2. Login page:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL/login.php")
echo "HTTP $HTTP_CODE"

# Static assets
echo ""
echo "3. CSS:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL/tsisip/css/tsisip-theme.css")
echo "HTTP $HTTP_CODE"

# Database
echo ""
echo "4. Database:"
docker compose exec -T postgres pg_isready -U opensips 2>/dev/null || echo "Not available"

# Containers
echo ""
echo "5. Containers:"
docker compose ps --format "table {{.Name}}\t{{.Status}}" 2>/dev/null || echo "Docker not available"

# Disk
echo ""
echo "6. Disk:"
df -h / | tail -1

# Memory
echo ""
echo "7. Memory:"
free -h | grep Mem

# Git
echo ""
echo "8. Git:"
echo "Commits: $(git log --oneline | wc -l)"
echo "Branch: $(git branch --show-current)"

echo ""
echo "=== Health Check Complete ==="
