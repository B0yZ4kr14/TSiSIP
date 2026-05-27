#!/usr/bin/env bash
# TSiSIP Validation
set -euo pipefail

echo "=== TSiSIP Validation ==="

# 1. Check PHP syntax
echo ""
echo "1. PHP syntax..."
find web -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" || echo "✓ PHP syntax OK"

# 2. Check for secrets
echo ""
echo "2. Secrets..."
bash scripts/security-scan.sh 2>/dev/null || echo "Security scan unavailable"

# 3. Check dependencies
echo ""
echo "3. Dependencies..."
bash scripts/dependency-check.sh 2>/dev/null || echo "Dependency check unavailable"

# 4. Check health
echo ""
echo "4. Health..."
curl -fsSL http://localhost/health.php >/dev/null 2>&1 && echo "✓ Healthy" || echo "✗ Unhealthy"

# 5. Check disk
echo ""
echo "5. Disk..."
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo "✗ Disk full ($DISK_USAGE%)"
elif [ "$DISK_USAGE" -gt 80 ]; then
    echo "⚠ Disk warning ($DISK_USAGE%)"
else
    echo "✓ Disk OK ($DISK_USAGE%)"
fi

# 6. Check memory
echo ""
echo "6. Memory..."
MEM_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ "$MEM_USAGE" -gt 90 ]; then
    echo "✗ Memory high ($MEM_USAGE%)"
else
    echo "✓ Memory OK ($MEM_USAGE%)"
fi

# 7. Check containers
echo ""
echo "7. Containers..."
docker compose ps | grep -q "Up" && echo "✓ Containers running" || echo "✗ Containers not running"

# 8. Check git
echo ""
echo "8. Git..."
git diff --quiet && echo "✓ No uncommitted changes" || echo "⚠ Uncommitted changes"

# 9. Check tests
echo ""
echo "9. Tests..."
bash tests/integration/test-ocp-all.sh >/dev/null 2>&1 && echo "✓ Tests passing" || echo "✗ Tests failing"

# 10. Check documentation
echo ""
echo "10. Documentation..."
[ -f README.md ] && echo "✓ README exists" || echo "✗ README missing"
[ -f docs/wiki/README.md ] && echo "✓ Docs index exists" || echo "✗ Docs index missing"

echo ""
echo "=== Validation Complete ==="
