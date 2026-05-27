#!/usr/bin/env bash
# TSiSIP Cleanup Old Files
set -euo pipefail

echo "=== TSiSIP Cleanup ==="

# Old logs
echo ""
echo "Removing old logs (>30 days)..."
find logs -name "*.log" -mtime +30 -delete 2>/dev/null || true

# Old backups
echo ""
echo "Removing old backups (>30 days)..."
find backups -name "*.sql.gz" -mtime +30 -delete 2>/dev/null || true

# Old reports
echo ""
echo "Removing old reports (>30 days)..."
find . -name "tsisip-report-*.md" -mtime +30 -delete 2>/dev/null || true

# Old cache
echo ""
echo "Removing old cache files..."
find cache -type f -mtime +7 -delete 2>/dev/null || true

# Docker cleanup
echo ""
echo "Docker cleanup..."
docker system prune -f 2>/dev/null || true

echo ""
echo "=== Cleanup Complete ==="
