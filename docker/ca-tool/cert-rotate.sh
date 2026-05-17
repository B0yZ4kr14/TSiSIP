#!/bin/bash
# TSiSIP Certificate Rotation Script
# Generates new certificates and stages them for deployment

set -euo pipefail

CA_DIR="${CA_DIR:-/ca}"
INT_DIR="$CA_DIR/intermediate"
OUTPUT_DIR="${OUTPUT_DIR:-/ca/output}"
SECRETS_DIR="${SECRETS_DIR:-/ca/secrets}"
BACKUP_DIR="${BACKUP_DIR:-/ca/backup}"

usage() {
    echo "Usage: $0 <action> [options]"
    echo ""
    echo "Actions:"
    echo "  stage    - Generate new certificates and stage to secrets/"
    echo "  rollback - Restore previous certificates from backup/"
    echo "  verify   - Verify current certificate chain"
    echo ""
    echo "Options for stage:"
    echo "  --cn <common_name>     - Server CN (default: tsiapp.io)"
    echo "  --san <alt_names>      - Subject alt names"
    exit 1
}

if [ $# -lt 1 ]; then
    usage
fi

ACTION="$1"
shift

CN="tsiapp.io"
SAN="DNS:tsiapp.io"

while [ $# -gt 0 ]; do
    case "$1" in
        --cn) CN="$2"; shift 2 ;;
        --san) SAN="$2"; shift 2 ;;
        *) echo "Unknown option: $1"; usage ;;
    esac
done

stage_certs() {
    echo "[CERT-ROTATE] Staging new certificates..."
    
    # Create backup of current certs
    mkdir -p "$BACKUP_DIR"
    if [ -f "$SECRETS_DIR/server.crt" ]; then
        local backup_timestamp=$(date +%Y%m%d_%H%M%S)
        local backup_path="$BACKUP_DIR/certs-$backup_timestamp"
        mkdir -p "$backup_path"
        cp -a "$SECRETS_DIR/"*.crt "$SECRETS_DIR/"*.key "$backup_path/" 2>/dev/null || true
        echo "[CERT-ROTATE] Backup created: $backup_path"
    fi
    
    # Generate new server certificate
    /usr/local/bin/cert-gen.sh server --cn "$CN" --san "$SAN"
    
    # Copy to secrets directory
    mkdir -p "$SECRETS_DIR"
    cp "$OUTPUT_DIR/server.crt" "$SECRETS_DIR/"
    cp "$OUTPUT_DIR/server.key" "$SECRETS_DIR/"
    cp "$OUTPUT_DIR/ca-chain.crt" "$SECRETS_DIR/ca.crt"
    chmod 400 "$SECRETS_DIR/server.key"
    chmod 444 "$SECRETS_DIR/server.crt" "$SECRETS_DIR/ca.crt"
    
    # Generate new CRL
    echo "[CERT-ROTATE] Updating CRL..."
    openssl ca -gencrl \
        -keyfile "$INT_DIR/private/intermediate.key" \
        -cert "$INT_DIR/certs/intermediate.crt" \
        -out "$SECRETS_DIR/crl.pem" \
        -config <(cat <<CONF
[ca]
default_ca = CA_default

[CA_default]
dir = $INT_DIR
certificate = \$dir/certs/intermediate.crt
crl_dir = \$dir/crl
private_key = \$dir/private/intermediate.key
crlnumber = \$dir/crlnumber
default_crl_days = 30
default_md = sha256
CONF
)
    
    echo "[CERT-ROTATE] Certificates staged to $SECRETS_DIR"
    echo "[CERT-ROTATE] To deploy: docker compose up -d (reloads secrets)"
    echo "[CERT-ROTATE] Then: opensipsctl fifo tls_reload"
}

rollback_certs() {
    echo "[CERT-ROTATE] Rolling back to previous certificates..."
    
    # Find most recent backup
    local latest_backup=$(ls -td "$BACKUP_DIR"/* 2>/dev/null | head -1)
    
    if [ -z "$latest_backup" ] || [ ! -d "$latest_backup" ]; then
        echo "[CERT-ROTATE] ERROR: No backup found for rollback"
        exit 1
    fi
    
    echo "[CERT-ROTATE] Restoring from: $latest_backup"
    cp -a "$latest_backup/"*.crt "$latest_backup/"*.key "$SECRETS_DIR/" 2>/dev/null || true
    
    echo "[CERT-ROTATE] Rollback complete. Restart containers to apply."
}

verify_certs() {
    echo "[CERT-ROTATE] Verifying certificate chain..."
    
    if [ ! -f "$SECRETS_DIR/server.crt" ]; then
        echo "[CERT-ROTATE] ERROR: server.crt not found in $SECRETS_DIR"
        exit 1
    fi
    
    # Verify chain
    if openssl verify -CAfile "$SECRETS_DIR/ca.crt" "$SECRETS_DIR/server.crt"; then
        echo "[CERT-ROTATE] Certificate chain verified OK"
    else
        echo "[CERT-ROTATE] ERROR: Certificate chain verification failed"
        exit 1
    fi
    
    # Show certificate info
    echo "[CERT-ROTATE] Current server certificate:"
    openssl x509 -in "$SECRETS_DIR/server.crt" -noout -subject -dates -serial
    
    # Check expiry
    local expiry=$(openssl x509 -in "$SECRETS_DIR/server.crt" -noout -enddate | cut -d= -f2)
    local expiry_epoch=$(date -d "$expiry" +%s)
    local now_epoch=$(date +%s)
    local days_until_expiry=$(( (expiry_epoch - now_epoch) / 86400 ))
    
    echo "[CERT-ROTATE] Days until expiry: $days_until_expiry"
    
    if [ $days_until_expiry -lt 30 ]; then
        echo "[CERT-ROTATE] WARNING: Certificate expires in less than 30 days!"
    fi
}

case "$ACTION" in
    stage)
        stage_certs
        ;;
    rollback)
        rollback_certs
        ;;
    verify)
        verify_certs
        ;;
    *)
        echo "Unknown action: $ACTION"
        usage
        ;;
esac
