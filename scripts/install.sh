#!/usr/bin/env bash
# TSiSIP Quick Install Script
set -euo pipefail

echo "=== TSiSIP Quick Installer ==="

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    sudo usermod -aG docker "$USER"
    echo "Docker installed. Please log out and back in."
    exit 0
fi

# Check Docker Compose
if ! command -v docker compose &> /dev/null; then
    echo "Docker Compose not found. Please install it."
    exit 1
fi

# Setup
echo "Setting up TSiSIP..."
mkdir -p secrets logs backups

# Copy environment
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from example"
fi

# Generate secrets
if [ ! -f secrets/db_password ]; then
    openssl rand -base64 32 > secrets/db_password
    echo "Generated database password"
fi

if [ ! -f secrets/auth_secret ]; then
    openssl rand -base64 32 > secrets/auth_secret
    echo "Generated auth secret"
fi

if [ ! -f secrets/topology_secret ]; then
    openssl rand -base64 32 > secrets/topology_secret
    echo "Generated topology secret"
fi

# Build and start
echo "Building images..."
docker compose build

echo "Starting services..."
docker compose up -d

# Wait for database
echo "Waiting for database..."
sleep 5

# Run migrations
echo "Running migrations..."
for f in db/init/*.sql; do
    docker compose exec -T postgres psql -U opensips -d opensips -f "/docker-entrypoint-initdb.d/$(basename "$f")" 2>/dev/null || true
done

# Verify
echo "Verifying..."
if curl -fsSL http://localhost/health.php >/dev/null 2>&1; then
    echo "✓ TSiSIP is running!"
    echo "Dashboard: http://localhost/login.php"
    echo "Health: http://localhost/health.php"
else
    echo "⚠ Health check failed. Check logs: docker compose logs"
fi

echo "=== Installation Complete ==="
