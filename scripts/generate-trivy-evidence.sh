#!/bin/bash
# scripts/generate-trivy-evidence.sh
set -euo pipefail
# Run against all vps-lite images

IMAGES=(
  "ghcr.io/b0yz4kr14/tsisip/opensips:test"
  "ghcr.io/b0yz4kr14/tsisip/rtpengine:test"
  "ghcr.io/b0yz4kr14/tsisip/postgres:test"
  "ghcr.io/b0yz4kr14/tsisip/ocp:test"
  "ghcr.io/b0yz4kr14/tsisip/asterisk:test"
  "ghcr.io/b0yz4kr14/tsisip/backup:test"
  "ghcr.io/b0yz4kr14/tsisip/certbot:test"
  "ghcr.io/b0yz4kr14/tsisip/certbot_exporter:test"
)

mkdir -p docs/security/evidence/022-vps-go-live

for img in "${IMAGES[@]}"; do
    echo "=== Scanning $img ==="
    trivy image --severity HIGH,CRITICAL "$img" \
        --format json \
        --output "docs/security/evidence/022-vps-go-live/trivy-$(echo "$img" | tr '/' '-').json" 2>/dev/null || echo "Trivy not installed or scan failed for $img"
done

echo "Consolidating report..."
jq -s '[.[] | .Results[]? | select(.Vulnerabilities) | .Vulnerabilities[] | {VulnerabilityID, Severity, PkgName, Title}]' \
    docs/security/evidence/022-vps-go-live/trivy-*.json > \
    docs/security/evidence/022-vps-go-live/trivy-consolidated.json 2>/dev/null || echo "No vulnerability data to consolidate"

echo "Trivy evidence generation complete"
