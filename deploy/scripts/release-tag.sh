#!/usr/bin/env bash
# TSiSIP Release Tag Script
# Generates semver tags, creates release manifest with image-to-digest mappings,
# and updates docker-compose environment for deterministic deployment.
#
# Usage:
#   ./deploy/scripts/release-tag.sh [tag_suffix]
#
# Examples:
#   ./deploy/scripts/release-tag.sh              # generates v2026.05.26-1
#   ./deploy/scripts/release-tag.sh hotfix-1     # generates v2026.05.26-hotfix-1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
RELEASE_DIR="${PROJECT_ROOT}/deploy/releases"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.vps.yml"

# Determine tag
DATE_PREFIX="$(date -u +%Y.%m.%d)"
SUFFIX="${1:-1}"
TAG="v${DATE_PREFIX}-${SUFFIX}"

echo "TSiSIP Release Tag"
echo "=================="
echo "  Tag: ${TAG}"
echo ""

mkdir -p "${RELEASE_DIR}"

# Image list from docker-compose.vps.yml
IMAGES=$(grep "image:" "${COMPOSE_FILE}" | sed 's/.*image: //' | sed "s/\${TSISIP_IMAGE_TAG:?must be set}/${TAG}/g" | sort -u)

MANIFEST_FILE="${RELEASE_DIR}/release-manifest-${TAG}.json"

cat > "${MANIFEST_FILE}" << MANIFESTEOF
{
  "release": "${TAG}",
  "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "images": {
MANIFESTEOF

FIRST=true
for IMAGE in ${IMAGES}; do
    # Pull and get digest
    echo "Resolving digest for ${IMAGE} ..."
    DIGEST=$(docker pull "${IMAGE}" 2>/dev/null | grep -oE 'sha256:[a-f0-9]{64}' | tail -1 || true)
    if [[ -z "${DIGEST}" ]]; then
        # Try to get digest from local image
        DIGEST=$(docker inspect --format='{{index .RepoDigests 0}}' "${IMAGE}" 2>/dev/null | grep -oE 'sha256:[a-f0-9]{64}' || true)
    fi
    if [[ -z "${DIGEST}" ]]; then
        DIGEST="unknown"
    fi

    if [[ "${FIRST}" == "true" ]]; then
        FIRST=false
    else
        echo "," >> "${MANIFEST_FILE}"
    fi

    cat >> "${MANIFEST_FILE}" << IMAGEEOF
    "${IMAGE}": {
      "tag": "${TAG}",
      "digest": "${DIGEST}"
    }
IMAGEEOF
    echo "  -> ${DIGEST}"
done

cat >> "${MANIFEST_FILE}" << MANIFESTEOF

  }
}
MANIFESTEOF

echo ""
echo "Release manifest: ${MANIFEST_FILE}"
echo ""

# Create .env snippet for deployment
cat > "${RELEASE_DIR}/.env-${TAG}" << ENVEOF
# TSiSIP Release ${TAG}
# Generated at $(date -u +%Y-%m-%dT%H:%M:%SZ)
TSISIP_IMAGE_TAG=${TAG}
ENVEOF

echo "Environment file: ${RELEASE_DIR}/.env-${TAG}"
echo ""

# Update current .env if it exists
if [[ -f "${PROJECT_ROOT}/.env" ]]; then
    echo "Updating ${PROJECT_ROOT}/.env with TSISIP_IMAGE_TAG=${TAG}"
    if grep -q "^TSISIP_IMAGE_TAG=" "${PROJECT_ROOT}/.env"; then
        sed -i "s/^TSISIP_IMAGE_TAG=.*/TSISIP_IMAGE_TAG=${TAG}/" "${PROJECT_ROOT}/.env"
    else
        echo "TSISIP_IMAGE_TAG=${TAG}" >> "${PROJECT_ROOT}/.env"
    fi
else
    echo "Creating ${PROJECT_ROOT}/.env with TSISIP_IMAGE_TAG=${TAG}"
    echo "TSISIP_IMAGE_TAG=${TAG}" > "${PROJECT_ROOT}/.env"
fi

echo ""
echo "Release ${TAG} ready."
echo "  docker compose -f docker-compose.vps.yml --env-file .env up -d"
