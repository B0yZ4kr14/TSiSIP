#!/usr/bin/env bash
# TSiSIP Version Bump
set -euo pipefail

PART="${1:-patch}"

echo "=== TSiSIP Version Bump ($PART) ==="

# Get current version
CURRENT=$(grep -o '1\.[0-9]\+\.[0-9]\+' web/about.php | head -1)
echo "Current: $CURRENT"

# Bump version
MAJOR=$(echo "$CURRENT" | cut -d. -f1)
MINOR=$(echo "$CURRENT" | cut -d. -f2)
PATCH=$(echo "$CURRENT" | cut -d. -f3)

case $PART in
    major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
    minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
    patch) PATCH=$((PATCH + 1)) ;;
esac

NEW="$MAJOR.$MINOR.$PATCH"
echo "New: $NEW"

# Update files
sed -i "s/$CURRENT/$NEW/g" web/about.php
sed -i "s/$CURRENT/$NEW/g" web/health.php

git add -A
git commit -m "chore: Bump version to $NEW"

echo "✓ Version bumped to $NEW"
