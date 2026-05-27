#!/bin/bash
# scripts/verify-tls-chain.sh
set -euo pipefail

echo "=== Certificate Validity ==="
docker compose exec certbot openssl x509 -in /etc/letsencrypt/live/tsiapp.io/fullchain.pem -noout -dates -subject -issuer 2>/dev/null || echo "Certificate not yet issued"

echo "=== Certificate Chain ==="
docker compose exec certbot openssl crl2pkcs7 -nocrl -certfile /etc/letsencrypt/live/tsiapp.io/fullchain.pem | openssl pkcs7 -print_certs -noout | grep subject 2>/dev/null || echo "Certificate not yet issued"

echo "=== Auto-Rotation Configuration ==="
docker compose exec certbot cat /etc/letsencrypt/renewal/tsiapp.io.conf | grep renew_before_expiry 2>/dev/null || echo "Renewal config not yet created"

echo "=== Deploy Hook ==="
docker compose exec certbot ls -la /etc/letsencrypt/renewal-hooks/deploy/ 2>/dev/null || echo "Deploy hooks not yet configured"
