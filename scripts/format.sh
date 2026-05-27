#!/usr/bin/env bash
# TSiSIP Format
set -euo pipefail

echo "=== TSiSIP Format ==="

# Find PHP files and basic formatting
find web -name "*.php" | while read -r file; do
    # Basic formatting: ensure final newline
    if [ -n "$(tail -c 1 "$file")" ]; then
        echo >> "$file"
    fi
done

echo "✓ Formatting complete"
echo "=== Format Complete ==="
