#!/bin/bash
# TSiSIP Certificate Generation Script
# Generates server certificates for OpenSIPS and client certificates for trunks

set -euo pipefail

CA_DIR="${CA_DIR:-/ca}"
INT_DIR="$CA_DIR/intermediate"
OUTPUT_DIR="${OUTPUT_DIR:-/ca/output}"

usage() {
    echo "Usage: $0 <type> [options]"
    echo "  type: server|client"
    echo ""
    echo "  Server certificate:"
    echo "    $0 server --cn <common_name> --san <subject_alt_names>"
    echo "    Example: $0 server --cn tsiapp.io --san 'DNS:tsiapp.io,IP:192.0.2.1'"
    echo ""
    echo "  Client certificate:"
    echo "    $0 client --cn <trunk_id>"
    echo "    Example: $0 client --cn trunk-carrier-01"
    exit 1
}

if [ $# -lt 1 ]; then
    usage
fi

CERT_TYPE="$1"
shift

# Parse arguments
CN=""
SAN=""
while [ $# -gt 0 ]; do
    case "$1" in
        --cn) CN="$2"; shift 2 ;;
        --san) SAN="$2"; shift 2 ;;
        *) echo "Unknown option: $1"; usage ;;
    esac
done

if [ -z "$CN" ]; then
    echo "Error: --cn is required"
    usage
fi

# Check intermediate CA exists
if [ ! -f "$INT_DIR/private/intermediate.key" ]; then
    echo "Error: Intermediate CA not initialized. Run ca-init.sh first."
    exit 1
fi

CERT_DAYS=365
KEY_SIZE=4096

generate_server_cert() {
    echo "[CERT-GEN] Generating server certificate for: $CN"
    
    # Generate private key
    openssl genrsa -out "$OUTPUT_DIR/server.key" $KEY_SIZE 2>/dev/null
    chmod 400 "$OUTPUT_DIR/server.key"
    
    # Create config with SANs
    local config_file="/tmp/server-$CN.cnf"
    cat > "$config_file" << CONF
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req
prompt = no

[req_distinguished_name]
CN = $CN
O = TSiSIP
C = BR

[v3_req]
keyUsage = keyEncipherment, dataEncipherment, digitalSignature
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
CONF

    # Parse SANs
    local i=1
    IFS=',' read -ra SAN_ARRAY <<< "$SAN"
    for san in "${SAN_ARRAY[@]}"; do
        san=$(echo "$san" | xargs)
        if [[ "$san" == DNS:* ]]; then
            echo "DNS.$i = ${san#DNS:}" >> "$config_file"
            ((i++))
        elif [[ "$san" == IP:* ]]; then
            echo "IP.$i = ${san#IP:}" >> "$config_file"
            ((i++))
        fi
    done
    
    # Generate CSR
    openssl req -new \
        -key "$OUTPUT_DIR/server.key" \
        -config "$config_file" \
        -out "$OUTPUT_DIR/server.csr"
    
    # Sign with intermediate CA
    openssl x509 -req -in "$OUTPUT_DIR/server.csr" \
        -CA "$INT_DIR/certs/intermediate.crt" \
        -CAkey "$INT_DIR/private/intermediate.key" \
        -CAcreateserial \
        -out "$OUTPUT_DIR/server.crt" \
        -days $CERT_DAYS \
        -sha256 \
        -extensions v3_req \
        -extfile "$config_file"
    
    # Create full chain
    cat "$OUTPUT_DIR/server.crt" "$INT_DIR/certs/intermediate.crt" "$CA_DIR/root/certs/ca.crt" > "$OUTPUT_DIR/server-fullchain.crt"
    
    rm -f "$config_file" "$OUTPUT_DIR/server.csr"
    
    echo "[CERT-GEN] Server certificate generated:"
    openssl x509 -in "$OUTPUT_DIR/server.crt" -noout -subject -dates
}

generate_client_cert() {
    echo "[CERT-GEN] Generating client certificate for trunk: $CN"
    
    local cert_name="client-$CN"
    
    # Generate private key
    openssl genrsa -out "$OUTPUT_DIR/$cert_name.key" $KEY_SIZE 2>/dev/null
    chmod 400 "$OUTPUT_DIR/$cert_name.key"
    
    # Generate CSR
    openssl req -new \
        -key "$OUTPUT_DIR/$cert_name.key" \
        -subj "/C=BR/O=TSiSIP/CN=$CN" \
        -out "$OUTPUT_DIR/$cert_name.csr"
    
    # Create client cert extensions
    local ext_file="/tmp/client-$CN.ext"
    cat > "$ext_file" << CONF
basicConstraints = CA:FALSE
nsCertType = client
nsComment = "TSiSIP Client Certificate"
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid,issuer
keyUsage = critical, nonRepudiation, digitalSignature, keyEncipherment
extendedKeyUsage = clientAuth
CONF
    
    # Sign with intermediate CA
    openssl x509 -req -in "$OUTPUT_DIR/$cert_name.csr" \
        -CA "$INT_DIR/certs/intermediate.crt" \
        -CAkey "$INT_DIR/private/intermediate.key" \
        -CAcreateserial \
        -out "$OUTPUT_DIR/$cert_name.crt" \
        -days $CERT_DAYS \
        -sha256 \
        -extfile "$ext_file"
    
    # Create PKCS#12 bundle for trunk operators
    openssl pkcs12 -export \
        -in "$OUTPUT_DIR/$cert_name.crt" \
        -inkey "$OUTPUT_DIR/$cert_name.key" \
        -certfile "$OUTPUT_DIR/ca-chain.crt" \
        -out "$OUTPUT_DIR/$cert_name.p12" \
        -passout pass:tsisip-client
    
    rm -f "$ext_file" "$OUTPUT_DIR/$cert_name.csr"
    
    echo "[CERT-GEN] Client certificate generated:"
    openssl x509 -in "$OUTPUT_DIR/$cert_name.crt" -noout -subject -dates
    echo "[CERT-GEN] PKCS#12 bundle: $OUTPUT_DIR/$cert_name.p12 (password: tsisip-client)"
}

case "$CERT_TYPE" in
    server)
        if [ -z "$SAN" ]; then
            echo "Warning: No SAN specified. Using CN as DNS SAN."
            SAN="DNS:$CN"
        fi
        generate_server_cert
        ;;
    client)
        generate_client_cert
        ;;
    *)
        echo "Unknown certificate type: $CERT_TYPE"
        usage
        ;;
esac

echo "[CERT-GEN] Certificate generation complete."
