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
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERROR]${NC} $*" >&2; }

if [ -z "${GITHUB_TOKEN:-}" ]; then
    error "GITHUB_TOKEN is not set. Run: source /tmp/tsisip-secrets.XXXXXX"
    exit 1
fi

info "Checking if repository $OWNER/$REPO exists..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    "$API_BASE/repos/$OWNER/$REPO")

if [ "$HTTP_STATUS" = "200" ]; then
    warn "Repository $OWNER/$REPO already exists."
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
            "gitignore_template": "Docker",
            "license_template": "apache-2.0"
        }' \
        "$API_BASE/user/repos" | jq -r '.html_url // .message'
    info "Repository created successfully."
else
    error "Unexpected GitHub API status: $HTTP_STATUS"
    exit 1
fi
