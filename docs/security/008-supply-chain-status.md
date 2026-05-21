# Supply Chain Security Status (SBOM / VEX / SLSA)

**Date**: 2026-05-21
**Scope**: Docker image build pipeline, GitHub Actions CI/CD
**Assessor**: Architecture Guard + Security Governance preset

## SBOM (Software Bill of Materials)

**Status**: Not Implemented
**Current State**: Trivy scans images for CVEs but does not export a formal SBOM.
**Gap**: No CycloneDX or SPDX SBOM is produced for any TSiSIP image.
**Impact**: Cannot perform transitive dependency analysis or vendor risk assessment.

## VEX (Vulnerability Exploitability eXchange)

**Status**: Not Implemented
**Current State**: Trivy reports CVEs without exploitability context.
**Gap**: No VEX document explains whether detected CVEs are exploitable in TSiSIP's runtime context.
**Impact**: Every CVE triggers manual investigation; no automated "not affected" declaration.

## SLSA (Supply-chain Levels for Software Artifacts)

**Status**: Not Implemented
**Current State**: GitHub Actions build and push images to ghcr.io without provenance attestation.
**Gap**: No SLSA Level 1+ provenance; no build attestation chain.
**Impact**: Cannot cryptographically verify that a deployed image was built from a specific commit.

## Remediation Roadmap

| Priority | Task | Tool | Target Date |
|---|---|---|---|
| P1 | Generate SBOM on every build | `anchore/syft` or `docker/buildx` with `--sbom` | 2026-06-15 |
| P2 | Publish VEX with releases | `openvex/vexctl` or Trivy VEX output | 2026-06-30 |
| P3 | SLSA Level 1 provenance | `slsa-framework/slsa-github-generator` | 2026-07-15 |
| P4 | SLSA Level 2+ (signed builds) | Sigstore/cosign keyless signing | 2026-08-01 |

## Positive Practices Already in Place

- Base images pinned to SHA256 digests (no :latest).
- Trivy vulnerability scanning in CI.
- GitHub Actions use OIDC for registry auth (no long-lived tokens).
- Private registry (ghcr.io) with access controls.

## References
- NIST SSDF PW.4.1–PW.4.3
- SLSA specification: https://slsa.dev/
- OpenVEX: https://openvex.dev/
- .github/workflows/ci.yml
- .github/workflows/deploy.yml
