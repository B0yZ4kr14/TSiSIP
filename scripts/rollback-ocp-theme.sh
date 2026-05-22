#!/bin/bash
# TSiSIP OCP Theme Rollback Script
# Safely removes the TSiSIP rebranding layer and restores original OCP v9.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() { echo -e "${GREEN}[INFO]${NC} $*"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }

cd "$PROJECT_ROOT"

info "Starting TSiSIP OCP theme rollback..."

# 1. Verify we are in the right place
if [ ! -d "web/tsisip" ]; then
    warn "web/tsisip/ directory not found; theme may already be removed."
fi

# 2. Create backup of current TSiSIP files (just in case)
BACKUP_DIR=".rollback-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

if [ -d "web/tsisip" ]; then
    cp -r web/tsisip "$BACKUP_DIR/"
    info "Backed up web/tsisip/ to $BACKUP_DIR/"
fi

if [ -f "web/common/header.php" ]; then
    cp web/common/header.php "$BACKUP_DIR/header.php"
fi

if [ -f "web/css/main.css" ]; then
    cp web/css/main.css "$BACKUP_DIR/main.css"
fi

# 3. Remove TSiSIP theme directory
if [ -d "web/tsisip" ]; then
    rm -rf web/tsisip
    info "Removed web/tsisip/"
fi

# 4. Revert header.php if a git-tracked original exists
if git ls-files --error-unmatch web/common/header.php >/dev/null 2>&1; then
    git checkout -- web/common/header.php
    info "Reverted web/common/header.php to git-tracked version"
else
    warn "web/common/header.php is not git-tracked; manual revert required"
fi

# 5. Revert main.css if a git-tracked original exists
if git ls-files --error-unmatch web/css/main.css >/dev/null 2>&1; then
    git checkout -- web/css/main.css
    info "Reverted web/css/main.css to git-tracked version"
else
    warn "web/css/main.css is not git-tracked; manual revert required"
fi

# 6. Validate rollback
info "Validating rollback..."
if [ -d "web/tsisip" ]; then
    error "Rollback failed: web/tsisip/ still exists"
    exit 1
fi

if grep -q "tsisip" web/common/header.php 2>/dev/null; then
    error "Rollback failed: web/common/header.php still contains TSiSIP references"
    exit 1
fi

if grep -q "tsisip" web/css/main.css 2>/dev/null; then
    error "Rollback failed: web/css/main.css still contains TSiSIP references"
    exit 1
fi

info "Rollback completed successfully."
info "Original OCP v9 branding restored."
info "Backup preserved at: $BACKUP_DIR/"
