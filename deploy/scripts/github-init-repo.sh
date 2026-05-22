#!/bin/bash
# TSiSIP GitHub Repository Initialization
# Creates the TSiSIP repository under github.com/B0yZ4kr14/TSiSIP
# Requires: GITHUB_TOKEN environment variable (from discover-and-secrets.sh)

set -euo pipefail

OWNER="B0yZ4kr14"
REPO="TSiSIP"
API_BASE="https://api.github.com"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }
dbg()   { echo -e "${BLUE}[DEBUG]${NC} $*"; }

DRY_RUN=false
if [ "${1:-}" = "--dry-run" ]; then
    DRY_RUN=true
    info "Running in dry-run mode..."
fi

if [ -z "${GITHUB_TOKEN:-}" ]; then
    error "GITHUB_TOKEN is not set. Run: source /tmp/tsisip-secrets.XXXXXX"
    exit 1
fi

# T2.1: Validate token permissions (dry-run or real)
info "Validating GitHub token..."
TOKEN_USER=$(curl -s -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    "$API_BASE/user" | jq -r '.login // empty')

if [ -z "$TOKEN_USER" ]; then
    error "Invalid GitHub token or API rate limit exceeded"
    exit 1
fi
info "Token valid for user: $TOKEN_USER"

# Check token scopes
SCOPES=$(curl -s -I -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    "$API_BASE/user" | grep -i "x-oauth-scopes:" || true)
info "Token scopes: ${SCOPES:-none detected}"

if [ "$DRY_RUN" = true ]; then
    info "[DRY-RUN] Would create repository: $OWNER/$REPO"
    info "[DRY-RUN] Settings:"
    info "  - name: TSiSIP"
    info "  - description: TSiSIP — Docker-First SIP Edge Proxy Platform"
    info "  - private: false"
    info "  - auto_init: true"
    info "  - license_template: apache-2.0"
    info "[DRY-RUN] Token user $TOKEN_USER has access to create repos under $OWNER"
    exit 0
fi

info "Checking if repository $OWNER/$REPO exists..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    "$API_BASE/repos/$OWNER/$REPO")

if [ "$HTTP_STATUS" = "200" ]; then
    warn "Repository $OWNER/$REPO already exists."
    
    # T2.2: Verify settings
    info "Verifying repository settings..."
    REPO_DATA=$(curl -s \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        "$API_BASE/repos/$OWNER/$REPO")
    
    CURRENT_PRIVATE=$(echo "$REPO_DATA" | jq -r '.private')
    CURRENT_DESC=$(echo "$REPO_DATA" | jq -r '.description')
    
    if [ "$CURRENT_PRIVATE" != "false" ]; then
        warn "Repository is private (expected: public). Updating..."
        curl -s -X PATCH \
            -H "Authorization: token $GITHUB_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "Content-Type: application/json" \
            -d '{"private": false}' \
            "$API_BASE/repos/$OWNER/$REPO" > /dev/null
        info "Repository updated to public"
    fi
    
    if [ "$CURRENT_DESC" != "TSiSIP — Docker-First SIP Edge Proxy Platform" ]; then
        warn "Repository description mismatch. Updating..."
        curl -s -X PATCH \
            -H "Authorization: token $GITHUB_TOKEN" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "Content-Type: application/json" \
            -d '{"description": "TSiSIP — Docker-First SIP Edge Proxy Platform"}' \
            "$API_BASE/repos/$OWNER/$REPO" > /dev/null
        info "Repository description updated"
    fi
    
    info "Repository settings verified."
    exit 0
elif [ "$HTTP_STATUS" = "404" ]; then
    info "Creating repository $OWNER/$REPO..."
    curl -s -X POST \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Content-Type: application/json" \
        -d '{
            "name": "TSiSIP",
            "description": "TSiSIP — Docker-First SIP Edge Proxy Platform",
            "private": false,
            "auto_init": true,
            "license_template": "apache-2.0"
        }' \
        "$API_BASE/user/repos" | jq -r '.html_url // .message'
    info "Repository created successfully."
else
    error "Unexpected GitHub API status: $HTTP_STATUS"
    exit 1
fi
