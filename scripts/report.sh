#!/usr/bin/env bash
# TSiSIP Report Generator
set -euo pipefail

OUTPUT="${1:-tsisip-report-$(date +%Y%m%d).md"

echo "=== TSiSIP Report ==="
echo "Generating: $OUTPUT"

cat > "$OUTPUT" << REPORT
# TSiSIP Report

**Date**: $(date)
**Version**: $(grep -o '1\.[0-9]\+\.[0-9]\+' web/about.php | head -1)
**Commits**: $(git log --oneline | wc -l)

## Status

$(bash scripts/status.sh 2>/dev/null || echo "Status unavailable")

## Health

$(curl -fsSL http://localhost/health.php 2>/dev/null || echo "Health check failed")

## Disk Usage

$(df -h)

## Memory

$(free -h)

## Containers

$(docker compose ps 2>/dev/null || echo "Docker unavailable")

## Git

- Branch: $(git branch --show-current)
- Commits: $(git log --oneline | wc -l)
- Last commit: $(git log -1 --format="%h %s")

## Files

- PHP files: $(find web -name "*.php" | wc -l)
- JS files: $(find web -name "*.js" | wc -l)
- CSS files: $(find web -name "*.css" | wc -l)
- Tests: $(find tests -name "*.sh" | wc -l)
- Docs: $(find docs -name "*.md" | wc -l)

## End

Generated: $(date)
REPORT

echo "✓ Report saved to $OUTPUT"
