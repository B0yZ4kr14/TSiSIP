#!/bin/bash
set -euo pipefail

# TSiSIP Backup Encryption/Decryption Wrapper
# Uses AES-256-CBC with PBKDF2 via OpenSSL

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
            -in "$INPUT" -out "$OUTPUT" -pass pass:"$KEY"
        echo "Encrypted: $OUTPUT"
        ;;
    decrypt)
        # Decrypt
        openssl enc -aes-256-cbc -d -pbkdf2 -iter 10000 \
            -in "$INPUT" -out "$OUTPUT" -pass pass:"$KEY"
        echo "Decrypted: $OUTPUT"
        ;;
    *)
        usage
        ;;
esac
