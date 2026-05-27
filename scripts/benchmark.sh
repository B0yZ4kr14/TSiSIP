#!/usr/bin/env bash
# TSiSIP Benchmark
set -euo pipefail

URL="${1:-http://localhost}"

echo "=== TSiSIP Benchmark ==="
echo "URL: $URL"
echo "Time: $(date)"

# Health check
echo "Health check:"
curl -s -o /dev/null -w "Status: %{http_code}, Time: %{time_total}s\n" "$URL/health.php"

# Login page
echo "Login page:"
curl -s -o /dev/null -w "Status: %{http_code}, Time: %{time_total}s\n" "$URL/login.php"

# Dashboard (requires auth, so just check redirect)
echo "Dashboard:"
curl -s -L -o /dev/null -w "Status: %{http_code}, Time: %{time_total}s\n" "$URL/dashboard.php"

# Database query time
echo "Database query:"
docker compose exec -T postgres psql -U opensips -d opensips -c "\timing on
SELECT COUNT(*) FROM subscriber;" 2>/dev/null || echo "Database not available"

# Container stats
echo "Container stats:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null || echo "Docker not available"

echo "=== Benchmark Complete ==="
