#!/bin/bash
set -euo pipefail

# TSiSIP Backup Encryption/Decryption Wrapper
# Uses AES-256-CBC with PBKDF2 via OpenSSL
# Generates HMAC-SHA256 checksum post-encryption for tamper detection

ACTION="${1:-}"
INPUT="${2:-}"
OUTPUT="${3:-}"
ENCRYPTION_KEY_FILE="${ENCRYPTION_KEY_FILE:-/run/secrets/backup_encryption_key}"

usage() {
    echo "Usage: $0 <encrypt|decrypt> <input_file> <output_file>"
    exit 1
}

[ -z "$ACTION" ] || [ -z "$INPUT" ] || [ -z "$OUTPUT" ] && usage

# Read encryption key
if [ ! -f "$ENCRYPTION_KEY_FILE" ] || [ ! -s "$ENCRYPTION_KEY_FILE" ]; then
    echo "ERROR: Encryption key not found at $ENCRYPTION_KEY_FILE"
    exit 1
fi

KEY="$(cat "$ENCRYPTION_KEY_FILE")"

case "$ACTION" in
    encrypt)
        # Encrypt with AES-256-CBC, PBKDF2, random salt
        openssl enc -aes-256-cbc -salt -pbkdf2 -iter 10000 \
            -in "$INPUT" -out "$OUTPUT" -k "$KEY"

        # Generate HMAC-SHA256 for tamper detection
        openssl dgst -sha256 -hmac "$KEY" -binary "$OUTPUT" | \
            od -An -tx1 | tr -d ' \n' > "${OUTPUT}.hmac"

        echo "Encrypted: $OUTPUT"
        echo "HMAC: ${OUTPUT}.hmac"
        ;;
    decrypt)
        # Verify HMAC if present (graceful fallback for legacy files)
        HMAC_FILE="${INPUT}.hmac"
        if [ -f "$HMAC_FILE" ]; then
            EXPECTED_HMAC="$(cat "$HMAC_FILE" | tr -d '\n')"
            ACTUAL_HMAC="$(openssl dgst -sha256 -hmac "$KEY" -binary "$INPUT" | od -An -tx1 | tr -d ' \n')"
            if [ "$EXPECTED_HMAC" != "$ACTUAL_HMAC" ]; then
                echo "ERROR: HMAC verification failed for $INPUT -- file may be corrupted or tampered"
                exit 1
            fi
            echo "HMAC verified OK"
        fi

        # Decrypt
        openssl enc -aes-256-cbc -d -pbkdf2 -iter 10000 \
            -in "$INPUT" -out "$OUTPUT" -k "$KEY"
        echo "Decrypted: $OUTPUT"
        ;;
    *)
        usage
        ;;
esac
