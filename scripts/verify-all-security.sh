#!/bin/bash
# TSiSIP Security Verification Orchestrator
# Runs all SG-phase verification scripts and produces evidence
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
EVIDENCE_DIR="$PROJECT_ROOT/docs/security/evidence"
DATE=$(date +%Y%m%d)

mkdir -p "$EVIDENCE_DIR"

echo "=== TSiSIP Security Verification Suite ==="
echo "Date: $(date)"
echo ""

# SG3.3: Network Isolation
echo "--- SG3.3: Network Isolation ---"
if bash "$SCRIPT_DIR/verify-network-isolation.sh" > "$EVIDENCE_DIR/008-network-isolation-${DATE}.txt" 2>&1; then
    echo "PASS: Network isolation verified"
else
    echo "FAIL: Network isolation violations found (see evidence file)"
fi
echo ""

# SG3.4: Secrets Audit
echo "--- SG3.4: Secrets Audit ---"
if bash "$SCRIPT_DIR/verify-secrets-audit.sh" > "$EVIDENCE_DIR/008-secrets-audit-${DATE}.txt" 2>&1; then
    echo "PASS: Secrets audit passed"
else
    echo "FAIL: Secrets audit found issues (see evidence file)"
fi
echo ""

# SG3.5: Nginx TLS
echo "--- SG3.5: Nginx TLS ---"
if bash "$SCRIPT_DIR/verify-nginx-tls.sh" > "$EVIDENCE_DIR/008-nginx-tls-${DATE}.txt" 2>&1; then
    echo "PASS: Nginx TLS configuration verified"
else
    echo "FAIL: Nginx TLS issues found (see evidence file)"
fi
echo ""

# SG3.6: Health Checks
echo "--- SG3.6: Health Checks ---"
if bash "$SCRIPT_DIR/verify-health-checks.sh" > "$EVIDENCE_DIR/008-health-checks-${DATE}.txt" 2>&1; then
    echo "PASS: Health checks verified"
else
    echo "FAIL: Health check issues found (see evidence file)"
fi
echo ""

# SG4.1: Secret Age Audit (non-blocking)
echo "--- SG4.1: Secret Age Audit ---"
bash "$SCRIPT_DIR/secret-age-audit.sh"
echo ""

echo "=== Evidence archived to $EVIDENCE_DIR ==="
echo "Done."
