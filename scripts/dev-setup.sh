#!/usr/bin/env bash
# TSiSIP Development Setup
set -euo pipefail

echo "=== TSiSIP Dev Setup ==="

# Check requirements
echo "Checking requirements..."
command -v docker >/dev/null 2>&1 || { echo "Docker required"; exit 1; }
command -v docker compose >/dev/null 2>&1 || { echo "Docker Compose required"; exit 1; }
command -v git >/dev/null 2>&1 || { echo "Git required"; exit 1; }

# Setup git hooks
echo "Setting up git hooks..."
git config core.hooksPath .githooks

# Copy env
echo "Setting up environment..."
[ -f .env ] || cp .env.example .env

# Create dirs
echo "Creating directories..."
mkdir -p secrets logs backups cache

# Build
echo "Building..."
make build

# Start
echo "Starting..."
make up

# Wait
sleep 5

# Migrate
echo "Running migrations..."
bash scripts/migrate.sh

# Seed
echo "Seeding..."
bash scripts/seed.sh

echo "=== Dev Setup Complete ==="
echo "Dashboard: http://localhost/login.php"
