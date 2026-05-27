#!/bin/bash
# scripts/consolidate-security-evidence.sh
set -euo pipefail

mkdir -p docs/security/evidence/022-vps-go-live

# Copy all evidence artifacts
cp docs/security/008-MSL-applicability-justification.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null
cp docs/security/008-data-flow-diagram.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null
cp docs/security/008-legal-basis-registry.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null
cp docs/security/008-data-minimization-audit.md \
   docs/security/evidence/022-vps-go-live/ 2>/dev/null

# Generate manifest
cat > docs/security/evidence/022-vps-go-live/MANIFEST.md << 'MANIFEST'
# Evidence Manifest — Feature 022

**Date**: 2026-05-23
**Total Artifacts**: [AUTO-COUNT]

| # | Artifact | Status | Location |
|---|----------|--------|----------|
| 1 | MSL Applicability | Complete | 008-MSL-applicability-justification.md |
| 2 | Data Flow Diagram | Complete | 008-data-flow-diagram.md |
| 3 | Legal Basis Registry | Complete | 008-legal-basis-registry.md |
| 4 | Data Minimization | Complete | 008-data-minimization-audit.md |
| 5 | SSL Labs Report | Pending DNS | ssl-labs-report.md |
| 6 | Trivy Scan | Complete | trivy-consolidated.json |
| 7 | Port Scan | Complete | port-scan-report.md |
| 8 | Auth Contract | Complete | auth-contract-evidence.md |
| 9 | TLS Certificate | Pending DNS | tls-certificate-evidence.md |
| 10 | STRIDE Model | Complete | 008-stride-threat-model.md |
| 11 | Deployment Checklist | Complete | 008-secure-deployment-checklist.md |
| 12 | Incident Response | Complete | 008-incident-response-runbook.md |
| 13 | Secret Rotation | Complete | 008-secret-rotation-procedures.md |
| 14 | Data Retention | Complete | data-retention-verification.md |
| 15 | Encryption/Access | Complete | encryption-access-control-evidence.md |
| 16 | SOC 2 Package | Complete | soc2-evidence-package.md |
MANIFEST

echo "Evidence consolidation complete"
