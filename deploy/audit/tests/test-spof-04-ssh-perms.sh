#!/bin/bash
# SPoF 4 Test: SSH key permissions
# Hypothesis: Script warns when SSH key permissions are incorrect

set -euo pipefail

echo "[TEST] SPoF 4: SSH key permissions"

TMP_SSH_DIR="/tmp/test-ssh-$$"
mkdir -p "$TMP_SSH_DIR"

# Create a fake key with wrong permissions
FAKE_KEY="$TMP_SSH_DIR/id_ed25519"
echo "-----BEGIN OPENSSH PRIVATE KEY-----" > "$FAKE_KEY"
echo "fake-key-data" >> "$FAKE_KEY"
echo "-----END OPENSSH PRIVATE KEY-----" >> "$FAKE_KEY"
chmod 644 "$FAKE_KEY"

# Run discovery with fake SSH dir
output=$(SSH_DIR="$TMP_SSH_DIR" ../../scripts/discover-and-secrets.sh --check-only 2>&1 || true)

# Cleanup
rm -rf "$TMP_SSH_DIR"

# Verify warning about permissions
if echo "$output" | grep -q "should be 600"; then
    echo "[PASS] Script detected incorrect key permissions"
else
    echo "[FAIL] Script did not warn about permissions"
    exit 1
fi

echo "[PASS] SPoF 4 test passed"
