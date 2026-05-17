#!/bin/bash
# SPoF 3 Test: Token leak prevention
# Hypothesis: GITHUB_TOKEN never appears in script output

set -uo pipefail

echo "[TEST] SPoF 3: Token leak prevention"

# Create a temporary .env with a fake token
FAKE_TOKEN="ghp_TEST1234567890abcdef"
TMP_ENV="/tmp/test-env-$$"
echo "GITHUB_TOKEN=$FAKE_TOKEN" > "$TMP_ENV"
echo "TSiAPP_HOST=test.example.com" >> "$TMP_ENV"
echo "TSiAPP_USER=testuser" >> "$TMP_ENV"

# Create a fake vault
TMP_VAULT="/tmp/test-vault-$$"
echo "TSiHomeLab=fake-vault-key" > "$TMP_VAULT"

# Create fake SSH dir with key
TMP_SSH="/tmp/test-ssh-$$"
mkdir -p "$TMP_SSH"
echo "-----BEGIN OPENSSH PRIVATE KEY-----" > "$TMP_SSH/id_ed25519"
echo "fake-key-data" >> "$TMP_SSH/id_ed25519"
echo "-----END OPENSSH PRIVATE KEY-----" >> "$TMP_SSH/id_ed25519"
chmod 600 "$TMP_SSH/id_ed25519"

# Run discovery with fake env
output=$(ENV_FILE="$TMP_ENV" VAULT_FILE="$TMP_VAULT" SSH_DIR="$TMP_SSH" ../../scripts/discover-and-secrets.sh --check-only 2>&1)
exit_code=$?

# Cleanup
rm -f "$TMP_ENV" "$TMP_VAULT"
rm -rf "$TMP_SSH"

# Verify token is not in output
if echo "$output" | grep -q "$FAKE_TOKEN"; then
    echo "[FAIL] Token leaked in script output!"
    exit 1
else
    echo "[PASS] Token not found in output"
fi

# Verify only "redacted" message appears
if echo "$output" | grep -q "redacted"; then
    echo "[PASS] Token confirmed as redacted"
else
    echo "[FAIL] Token not marked as redacted"
    exit 1
fi

echo "[PASS] SPoF 3 test passed"
