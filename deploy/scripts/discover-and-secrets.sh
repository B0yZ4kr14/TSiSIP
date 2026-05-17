#!/bin/bash
# TSiSIP DevSecOps Discovery & Secret Management
# SAFETY: This script reads from user-local files ONLY. No secrets are logged.
# Usage: ./discover-and-secrets.sh [--check-only]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
VAULT_FILE="${VAULT_FILE:-$HOME/.tsi-vault}"
ENV_FILE="${ENV_FILE:-$HOME/.env}"
SSH_DIR="${SSH_DIR:-$HOME/.ssh}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }
dbg()   { echo -e "${BLUE}[DEBUG]${NC} $*"; }

CHECK_ONLY=false
if [ "${1:-}" = "--check-only" ]; then
    CHECK_ONLY=true
    info "Running in check-only mode..."
fi

# --- 1. Vault Discovery ---
info "Checking TSi-Vault..."
TSI_VAULT_KEY=""
if [ -f "$VAULT_FILE" ]; then
    TSI_VAULT_KEY=$(grep -E '^TSiHomeLab=' "$VAULT_FILE" 2>/dev/null | cut -d'=' -f2- || true)
    if [ -n "$TSI_VAULT_KEY" ]; then
        info "Vault key found (redacted)"
    else
        warn "Vault file exists but TSiHomeLab key not found"
    fi
else
    warn "Vault file not found at $VAULT_FILE"
fi

# --- 2. Environment File Discovery ---
info "Checking local environment file..."
GITHUB_TOKEN=""
VPS_HOST=""
VPS_USER=""
if [ -f "$ENV_FILE" ]; then
    while IFS='=' read -r key value; do
        case "$key" in
            GITHUB_TOKEN) GITHUB_TOKEN="$value" ;;
            TSiAPP_HOST)  VPS_HOST="$value" ;;
            TSiAPP_USER)  VPS_USER="$value" ;;
        esac
    done < <(grep -E '^(GITHUB_TOKEN|TSiAPP_HOST|TSiAPP_USER)=' "$ENV_FILE" 2>/dev/null || true)
    
    if [ -n "$GITHUB_TOKEN" ]; then
        info "GitHub token found (redacted)"
    fi
    if [ -n "$VPS_HOST" ] && [ -n "$VPS_USER" ]; then
        info "VPS target: $VPS_USER@$VPS_HOST (from ~/.env)"
    fi
else
    warn "Environment file not found at $ENV_FILE"
fi

# --- 3. SSH Key Discovery ---
info "Checking SSH keys..."
SSH_KEY_FOUND=""
SSH_KEY_TYPE=""
for keyfile in "$SSH_DIR"/id_ed25519 "$SSH_DIR"/id_rsa "$SSH_DIR"/tsiapp_key; do
    if [ -f "$keyfile" ]; then
        SSH_KEY_FOUND="$keyfile"
        if head -1 "$keyfile" | grep -q "OPENSSH PRIVATE KEY"; then
            SSH_KEY_TYPE="openssh"
        else
            SSH_KEY_TYPE="legacy"
        fi
        info "SSH key found: $keyfile (type: $SSH_KEY_TYPE)"
        break
    fi
done

# T1.1: Validate SSH key format (Ed25519 preferred)
if [ -n "$SSH_KEY_FOUND" ]; then
    if echo "$SSH_KEY_FOUND" | grep -q "ed25519"; then
        info "SSH key uses Ed25519 (recommended)"
    else
        warn "SSH key is not Ed25519. Consider: ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519"
    fi
    
    # Check key file permissions
    KEY_PERMS=$(stat -c "%a" "$SSH_KEY_FOUND" 2>/dev/null || stat -f "%Lp" "$SSH_KEY_FOUND" 2>/dev/null)
    if [ "$KEY_PERMS" != "600" ]; then
        warn "SSH key permissions are $KEY_PERMS (should be 600). Run: chmod 600 $SSH_KEY_FOUND"
    fi
fi

if [ -z "$SSH_KEY_FOUND" ]; then
    warn "No SSH private key found in $SSH_DIR"
fi

# --- 4. Validation ---
info "Validating configuration completeness..."
MISSING=()
[ -z "$TSI_VAULT_KEY" ] && MISSING+=("TSiHomeLab vault key")
[ -z "$GITHUB_TOKEN" ] && MISSING+=("GITHUB_TOKEN in ~/.env")
[ -z "$VPS_HOST" ]     && MISSING+=("TSiAPP_HOST in ~/.env")
[ -z "$VPS_USER" ]     && MISSING+=("TSiAPP_USER in ~/.env")
[ -z "$SSH_KEY_FOUND" ] && MISSING+=("SSH private key")

if [ ${#MISSING[@]} -gt 0 ]; then
    error "Missing required secrets:"
    printf '  - %s\n' "${MISSING[@]}"
    exit 1
fi

# --- 5. Check-only mode exit ---
if [ "$CHECK_ONLY" = true ]; then
    info "All secrets validated successfully (check-only mode)."
    exit 0
fi

# --- 6. Export for downstream tools (securely) ---
SECRETS_TEMP=$(mktemp /tmp/tsisip-secrets.XXXXXX)
chmod 600 "$SECRETS_TEMP"
cat > "$SECRETS_TEMP" << EOF
# TSiSIP Secrets — Auto-generated, delete after use
# Generated: $(date -Iseconds)
# WARNING: This file contains sensitive data. Delete immediately after use.
export TSI_VAULT_KEY='${TSI_VAULT_KEY}'
export GITHUB_TOKEN='${GITHUB_TOKEN}'
export TSiAPP_HOST='${VPS_HOST}'
export TSiAPP_USER='${VPS_USER}'
export TSiAPP_SSH_KEY='${SSH_KEY_FOUND}'
EOF

info "Secrets extracted successfully."
info "Source them with: source $SECRETS_TEMP"
info "Then delete the temp file: rm -f $SECRETS_TEMP"
