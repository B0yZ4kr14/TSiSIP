#!/usr/bin/env bash
# TSiSIP Changelog Generator
set -euo pipefail

FROM="${1:-$(git describe --tags --abbrev=0 2>/dev/null || echo '')}"
TO="${2:-HEAD}"

echo "=== TSiSIP Changelog ==="
echo "From: ${FROM:-first commit}"
echo "To: $TO"
echo ""

if [ -n "$FROM" ]; then
    RANGE="$FROM..$TO"
else
    RANGE="$TO"
fi

echo "## Features"
git log --oneline --grep="^feat" "$RANGE" 2>/dev/null || echo "- None"

echo ""
echo "## Fixes"
git log --oneline --grep="^fix" "$RANGE" 2>/dev/null || echo "- None"

echo ""
echo "## Documentation"
git log --oneline --grep="^docs" "$RANGE" 2>/dev/null || echo "- None"

echo ""
echo "## Tests"
git log --oneline --grep="^test" "$RANGE" 2>/dev/null || echo "- None"

echo ""
echo "## Other"
git log --oneline --grep="^chore\|^refactor\|^style" "$RANGE" 2>/dev/null || echo "- None"
