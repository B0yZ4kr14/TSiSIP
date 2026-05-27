#!/usr/bin/env bash
# TSiSIP Dependency Check
set -euo pipefail

echo "=== TSiSIP Dependency Check ==="

# Docker
echo ""
echo "Docker:"
docker --version

# Docker Compose
echo ""
echo "Docker Compose:"
docker compose version

# Git
echo ""
echo "Git:"
git --version

# PHP (if available)
echo ""
echo "PHP:"
php --version 2>/dev/null || echo "Not available (runs in container)"

# PostgreSQL (if available)
echo ""
echo "PostgreSQL client:"
psql --version 2>/dev/null || echo "Not available (runs in container)"

# cURL
echo ""
echo "cURL:"
curl --version | head -1

# Python
echo ""
echo "Python:"
python3 --version 2>/dev/null || python --version 2>/dev/null || echo "Not available"

# Node.js
echo ""
echo "Node.js:"
node --version 2>/dev/null || echo "Not available"

# Nginx
echo ""
echo "Nginx:"
nginx -v 2>/dev/null || echo "Not available (optional)"

# OpenSSL
echo ""
echo "OpenSSL:"
openssl version

# Make
echo ""
echo "Make:"
make --version | head -1

# Check container images
echo ""
echo "Docker images:"
docker images --format "table {{.Repository}}\t{{.Tag}}" | grep -E "opensips|postgres|php" | head -10 || echo "No relevant images"

echo ""
echo "=== Dependency Check Complete ==="
