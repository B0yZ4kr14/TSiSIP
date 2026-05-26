#!/usr/bin/env bash
# TSiSIP Rollback Script
# Rolls back to a previous release manifest.
#
# Usage:
#   ./deploy/scripts/rollback.sh <release_tag>
#
# Example:
#   ./deploy/scripts/rollback.sh v2026.05.26-1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
RELEASE_DIR="${PROJECT_ROOT}/deploy/releases"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <release_tag>"
    echo "Available releases:"
    ls -1 "${RELEASE_DIR}"/release-manifest-*.json 2>/dev/null | sed 's/.*release-manifest-//;s/\.json//' || echo "  (none found)"
    exit 1
fi

TAG="$1"
MANIFEST_FILE="${RELEASE_DIR}/release-manifest-${TAG}.json"
ENV_FILE="${RELEASE_DIR}/.env-${TAG}"

if [[ ! -f "${MANIFEST_FILE}" ]]; then
    echo "Error: Release manifest not found: ${MANIFEST_FILE}"
    echo "Available releases:"
    ls -1 "${RELEASE_DIR}"/release-manifest-*.json 2>/dev/null | sed 's/.*release-manifest-//;s/\.json//' || true
    exit 1
fi

echo "TSiSIP Rollback"
echo "==============="
echo "  Target release: ${TAG}"
echo "  Manifest: ${MANIFEST_FILE}"
echo ""

echo "Images in manifest:"
jq -r '.images | to_entries[] | "  \(.key) => \(.value.digest)"' "${MANIFEST_FILE}"

if [[ -f "${ENV_FILE}" ]]; then
    echo ""
    echo "Loading environment from ${ENV_FILE}"
    set -a
    source "${ENV_FILE}"
    set +a
else
    echo ""
    echo "Warning: Environment file not found. Using TSISIP_IMAGE_TAG=${TAG}"
    export TSISIP_IMAGE_TAG="${TAG}"
fi

echo ""
echo "Rolling back services..."
echo "Attempting to pull images (non-fatal if unavailable)..."
docker compose -f "${COMPOSE_FILE}" pull 2>/dev/null || true
docker compose -f "${COMPOSE_FILE}" up -d

echo ""
echo "Rollback to ${TAG} complete."
