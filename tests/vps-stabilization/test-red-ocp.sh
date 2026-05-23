#!/bin/bash
# T4.1 — RED OCP Endpoint Test
# Expects: OCP accessible at https://tsiapp.io/TSiSIP

set -euo pipefail

TARGET="https://tsiapp.io/TSiSIP"

echo "=== RED OCP Endpoint Test ==="
echo "Target: $TARGET"

if curl -fsSL "$TARGET" >/dev/null 2>&1; then
    echo "WARNING: OCP endpoint is accessible — RED phase may be complete"
    exit 0
else
    echo "RED CONFIRMED: OCP endpoint not accessible (expected in RED phase)"
    exit 1
fi
