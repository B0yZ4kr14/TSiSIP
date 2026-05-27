#!/usr/bin/env bash
# TSiSIP Status Check
set -euo pipefail

echo "=== TSiSIP Status ==="
echo "Time: $(date)"

# Container status
echo ""
echo "Containers:"
docker compose ps 2>/dev/null || echo "Docker not running"

# Health check
echo ""
echo "Health:"
curl -fsSL http://localhost/health.php 2>/dev/null || echo "Health check failed"

# Disk usage
echo ""
echo "Disk:"
df -h / | tail -1

# Memory
echo ""
echo "Memory:"
free -h | grep Mem

# Git status
echo ""
echo "Git:"
echo "Commits: $(git log --oneline | wc -l)"
echo "Branch: $(git branch --show-current)"

echo ""
echo "=== Status Complete ==="
