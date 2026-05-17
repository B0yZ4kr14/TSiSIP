#!/bin/bash
# TSiSIP CA Infrastructure Initialization
# Generates root CA and intermediate CA for SIP TLS certificates

set -euo pipefail

CA_DIR="${CA_DIR:-/ca}"
ROOT_DIR="$CA_DIR/root"
INT_DIR="$CA_DIR/intermediate"
OUTPUT_DIR="${OUTPUT_DIR:-/ca/output}"

# Configuration
ROOT_DAYS=3650        # 10 years
INT_DAYS=1825         # 5 years
KEY_SIZE=4096
HASH_ALG="sha256"

echo "[CA-INIT] Initializing TSiSIP PKI infrastructure..."

# Create directory structure
mkdir -p "$ROOT_DIR"/{private,certs,crl,newcerts}
mkdir -p "$INT_DIR"/{private,certs,crl,newcerts}
mkdir -p "$OUTPUT_DIR"
touch "$ROOT_DIR/index.txt"
touch "$INT_DIR/index.txt"
echo 1000 > "$ROOT_DIR/serial"
echo 1000 > "$INT_DIR/serial"

# Generate root CA private key
echo "[CA-INIT] Generating root CA key (RSA $KEY_SIZE)..."
openssl genrsa -out "$ROOT_DIR/private/ca.key" $KEY_SIZE 2>/dev/null
chmod 400 "$ROOT_DIR/private/ca.key"

# Generate root CA certificate
echo "[CA-INIT] Generating root CA certificate..."
openssl req -x509 -new -nodes \
    -key "$ROOT_DIR/private/ca.key" \
    -sha256 \
    -days $ROOT_DAYS \
    -subj "/C=BR/ST=SP/O=TSiSIP/CN=TSiSIP Root CA" \
    -out "$ROOT_DIR/certs/ca.crt"

# Generate intermediate CA private key
echo "[CA-INIT] Generating intermediate CA key (RSA $KEY_SIZE)..."
openssl genrsa -out "$INT_DIR/private/intermediate.key" $KEY_SIZE 2>/dev/null
chmod 400 "$INT_DIR/private/intermediate.key"

# Generate intermediate CA CSR
echo "[CA-INIT] Generating intermediate CA CSR..."
openssl req -new \
    -key "$INT_DIR/private/intermediate.key" \
    -subj "/C=BR/ST=SP/O=TSiSIP/CN=TSiSIP Intermediate CA" \
    -out "$INT_DIR/certs/intermediate.csr"

# Sign intermediate CA with root CA
echo "[CA-INIT] Signing intermediate CA with root CA..."
openssl x509 -req -in "$INT_DIR/certs/intermediate.csr" \
    -CA "$ROOT_DIR/certs/ca.crt" \
    -CAkey "$ROOT_DIR/private/ca.key" \
    -CAcreateserial \
    -out "$INT_DIR/certs/intermediate.crt" \
    -days $INT_DAYS \
    -sha256 \
    -extensions v3_ca

# Create certificate chain
cat "$INT_DIR/certs/intermediate.crt" "$ROOT_DIR/certs/ca.crt" > "$OUTPUT_DIR/ca-chain.crt"

# Copy outputs
cp "$ROOT_DIR/certs/ca.crt" "$OUTPUT_DIR/"
cp "$INT_DIR/certs/intermediate.crt" "$OUTPUT_DIR/"

echo "[CA-INIT] PKI infrastructure initialized successfully."
echo "[CA-INIT] Outputs in $OUTPUT_DIR:"
ls -la "$OUTPUT_DIR/"
echo ""
echo "[CA-INIT] IMPORTANT: Store root CA key securely (offline/HSM):"
ls -la "$ROOT_DIR/private/ca.key"
