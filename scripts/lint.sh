#!/usr/bin/env bash
# TSiSIP Lint
set -euo pipefail

echo "=== TSiSIP Lint ==="

# PHP syntax check
echo "Checking PHP syntax..."
find web -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors" || echo "✓ All PHP files valid"

# Check for secrets
echo "Checking for secrets..."
grep -r "password.*=" web/ --include="*.php" | grep -v "secrets/" | grep -v "password_hash" | grep -v "getenv" | head -5 || echo "✓ No hardcoded passwords"

# Check file permissions
echo "Checking file permissions..."
find web -type f -perm /111 | grep -v ".php" | head -5 || echo "✓ No executable non-scripts"

# Check for TODO/FIXME
echo "Checking for TODOs..."
grep -r "TODO\|FIXME" web/ --include="*.php" | head -10 || echo "✓ No TODOs found"

echo "=== Lint Complete ==="
