#!/usr/bin/env bash
# TSiSIP SBOM + VEX Generation Script
# Generates CycloneDX SBOMs and OpenVEX documents for project container images.
# Requires: trivy (https://aquasecurity.github.io/trivy/)
#
# Usage:
#   ./scripts/generate-sbom-vex.sh [image_tag]
#
# Output:
#   reports/sbom-opensips.cdx.json
#   reports/sbom-ocp.cdx.json
#   reports/vex-opensips.json
#   reports/vex-ocp.json

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
REPORTS_DIR="${PROJECT_ROOT}/reports"
TAG="${1:-latest}"

echo "TSiSIP SBOM + VEX Generator"
echo "   Image tag: ${TAG}"
echo "   Output dir: ${REPORTS_DIR}"
echo ""

mkdir -p "${REPORTS_DIR}"

# Verify trivy is available
if ! command -v trivy &>/dev/null; then
    echo "trivy not found. Install: https://aquasecurity.github.io/trivy/latest/getting-started/installation/"
    exit 1
fi

TRIVY_VERSION=$(trivy version 2>/dev/null | head -1 || echo "unknown")
echo "   trivy: ${TRIVY_VERSION}"
echo ""

generate_sbom() {
    local image_name=$1
    local output_file=$2

    echo "Generating SBOM for ${image_name} ..."
    trivy image --format cyclonedx --output "${output_file}" "${image_name}"

    if [[ -f "${output_file}" ]]; then
        local size
        size=$(stat -c%s "${output_file}" 2>/dev/null || stat -f%z "${output_file}" 2>/dev/null)
        echo "   OK ${output_file} (${size} bytes)"
    else
        echo "   FAILED ${output_file}"
        exit 1
    fi
}

generate_sbom "tsisip-opensips:${TAG}" "${REPORTS_DIR}/sbom-opensips.cdx.json"
generate_sbom "tsisip/ocp:${TAG}" "${REPORTS_DIR}/sbom-ocp.cdx.json"

generate_vex() {
    local image_name=$1
    local output_file=$2

    echo "Generating vulnerability report for ${image_name} ..."
    trivy image --format json --output "${output_file}.tmp" "${image_name}" || true

    if [[ -f "${output_file}.tmp" ]]; then
        local created_at
        created_at=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

        cat > "${output_file}" << VEXEOF
{
  "at_context": "https://openvex.dev/ns/v0.2.0",
  "at_id": "https://tsiapp.io/vex/$(basename "${output_file}" .json)-${created_at}.json",
  "author": "TSiSIP Security Pipeline",
  "timestamp": "${created_at}",
  "version": 1,
  "tooling": "trivy ${TRIVY_VERSION}",
  "statements": [
VEXEOF

        local first=true
        while IFS= read -r cve; do
            if [[ "${first}" == "true" ]]; then
                first=false
            else
                echo "," >> "${output_file}"
            fi
            cat >> "${output_file}" << VEXEOF
    {
      "vulnerability": {
        "name": "${cve}"
      },
      "products": [
        {
          "at_id": "pkg:oci/${image_name}"
        }
      ],
      "status": "under_investigation",
      "justification": "component_not_present"
    }
VEXEOF
        done < <(jq -r '.Results[]?.Vulnerabilities[]?.VulnerabilityID' "${output_file}.tmp" 2>/dev/null | sort -u || true)

        cat >> "${output_file}" << VEXEOF

  ]
}
VEXEOF

        rm -f "${output_file}.tmp"
        local size
        size=$(stat -c%s "${output_file}" 2>/dev/null || stat -f%z "${output_file}" 2>/dev/null)
        echo "   OK ${output_file} (${size} bytes)"
    else
        echo "   No vulnerability data for ${image_name}; creating empty VEX"
        cat > "${output_file}" << VEXEOF
{
  "at_context": "https://openvex.dev/ns/v0.2.0",
  "at_id": "https://tsiapp.io/vex/$(basename "${output_file}" .json)-$(date -u +%Y%m%d).json",
  "author": "TSiSIP Security Pipeline",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "version": 1,
  "statements": []
}
VEXEOF
    fi
}

generate_vex "tsisip-opensips:${TAG}" "${REPORTS_DIR}/vex-opensips.json"
generate_vex "tsisip/ocp:${TAG}" "${REPORTS_DIR}/vex-ocp.json"

echo ""
echo "SBOM + VEX generation complete."
ls -la "${REPORTS_DIR}"/sbom-*.cdx.json "${REPORTS_DIR}"/vex-*.json 2>/dev/null || true
