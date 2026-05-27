#!/usr/bin/env bash
# TSiSIP Git Stats
set -euo pipefail

echo "=== TSiSIP Git Stats ==="

# Total commits
echo ""
echo "Total commits: $(git log --oneline | wc -l)"

# Contributors
echo ""
echo "Contributors:"
git log --format='%an' | sort -u | wc -l | xargs echo "Unique:"

# Commits by author
echo ""
echo "Commits by author:"
git shortlog -sn | head -10

# Commits by date
echo ""
echo "Commits by date:"
git log --format='%ad' --date=short | sort | uniq -c | sort -rn | head -10

# Files changed
echo ""
echo "Files changed:"
git diff --stat 4b825dc642cb6eb9a060e54bf8d69288fbee4904 | tail -1

# Lines of code
echo ""
echo "Lines of code:"
find web -name "*.php" -exec wc -l {} + | tail -1
find web -name "*.js" -exec wc -l {} + | tail -1
find web -name "*.css" -exec wc -l {} + | tail -1

# Branches
echo ""
echo "Branches:"
git branch -a | wc -l | xargs echo "Total:"

# Tags
echo ""
echo "Tags:"
git tag | wc -l | xargs echo "Total:"

# Last commit
echo ""
echo "Last commit:"
git log -1 --format="%h %an %ad %s" --date=short

echo ""
echo "=== Git Stats Complete ==="
