#!/bin/bash
# SPoF 1 Test: Missing ~/.env
# Hypothesis: If ~/.env is missing, discovery script fails with clear message

set -euo pipefail

echo "[TEST] SPoF 1: Missing ~/.env"

# Backup original env
ENV_FILE="${ENV_FILE:-$HOME/.env}"
BACKUP="/tmp/.env.bak.$(date +%s)"

if [ -f "$ENV_FILE" ]; then
    cp "$ENV_FILE" "$BACKUP"
    rm -f "$ENV_FILE"
fi

# Run discovery script
set +e
output=$(../../scripts/discover-and-secrets.sh --check-only 2>&1)
exit_code=$?
set -e

# Restore env
if [ -f "$BACKUP" ]; then
    mv "$BACKUP" "$ENV_FILE"
fi

# Verify
if [ $exit_code -ne 0 ]; then
    echo "[PASS] Script failed as expected (exit code: $exit_code)"
else
    echo "[FAIL] Script should have failed without ~/.env"
    exit 1
fi

if echo "$output" | grep -q "GITHUB_TOKEN\|TSiAPP_HOST\|TSiAPP_USER"; then
    echo "[PASS] Script reported missing secrets clearly"
else
    echo "[FAIL] Missing secrets not clearly reported"
    exit 1
fi

echo "[PASS] SPoF 1 test passed"
