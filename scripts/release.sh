#!/usr/bin/env bash
# TSiSIP Release Script
set -euo pipefail

VERSION="${1:-1.0.0}"

echo "=== TSiSIP Release $VERSION ==="

# Update version in files
sed -i "s/1\.0\.0/$VERSION/g" web/about.php 2>/dev/null || true
sed -i "s/1\.0\.0/$VERSION/g" web/health.php 2>/dev/null || true

# Update changelog
echo "## [$VERSION] - $(date +%Y-%m-%d)" >> docs/CHANGELOG-2026-05.md

# Git tag
git add -A
git commit -m "release: Version $VERSION"
git tag -a "v$VERSION" -m "Release $VERSION"

echo "✓ Release $VERSION created"
echo "Push with: git push && git push --tags"
