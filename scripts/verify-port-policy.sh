#!/bin/bash
# scripts/verify-port-policy.sh
# Verify zero public Asterisk/PostgreSQL ports

echo "=== Docker Compose Port Audit ==="
docker compose -f docker-compose.vps.yml config | grep -E "ports:|expose:" -A 2

echo "=== Host Port Scan ==="
nmap -p 5060,5432,8084,9090,9093,22222 127.0.0.1 2>/dev/null || echo "nmap not installed"

echo "=== Asterisk/PostgreSQL Port Exposure ==="
docker compose -f docker-compose.vps.yml config | grep -E "(asterisk|postgres):" -A 20 | grep -E "ports:|published:"

echo "=== Expected Result ==="
echo "- Asterisk: NO published ports"
echo "- PostgreSQL: NO published ports"
echo "- OpenSIPS: 5060/udp, 5060/tcp"
echo "- OCP: 8084/tcp (loopback only)"
echo "- RTPengine: 10000-20000/udp"
