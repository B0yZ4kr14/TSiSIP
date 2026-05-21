# NIST SSDF v1.1 Gap Analysis

**Date**: 2026-05-21
**Scope**: TSiSIP Docker-first SIP edge proxy platform
**Assessor**: Architecture Guard + Security Governance preset

## PW.1 — Secure Design

| Practice | Status | Evidence |
|---|---|---|
| PW.1.1: Secure design principles | Met | security_constitution.md, architecture_constitution.md |
| PW.1.2: Threat modeling | Partial | Network trust boundaries documented; formal threat model N/A |

## PW.2 — Secure Coding

| Practice | Status | Evidence |
|---|---|---|
| PW.2.1: Secure coding practices | Met | PDO prepared statements, bcrypt, HA1-only, header sanitization |
| PW.2.2: Reusable code review | Partial | No formal code review checklist committed |

## PW.3 — Security Testing

| Practice | Status | Evidence |
|---|---|---|
| PW.3.1: Security test plans | Partial | Integration tests exist; no dedicated security test suite |
| PW.3.2: Security test execution | Partial | Trivy CI scan; no DAST/SAST beyond Trivy |

## PW.4 — SBOM / VEX / SLSA

| Practice | Status | Evidence |
|---|---|---|
| PW.4.1: SBOM generation | Not Met | No CycloneDX/SPDX SBOM produced |
| PW.4.2: VEX publication | Not Met | No VEX document for known CVEs |
| PW.4.3: SLSA provenance | Not Met | No build attestation or provenance chain |

## Gaps Summary

1. **SBOM Generation (HIGH)**: Docker images lack CycloneDX/SPDX SBOMs. Trivy scans but does not export SBOM.
2. **VEX Publication (MEDIUM)**: No VEX document explaining exploitability of detected CVEs.
3. **SLSA Provenance (MEDIUM)**: GitHub Actions do not generate SLSA Level 1+ attestations.
4. **Threat Model (LOW)**: No formal threat model document (STRIDE or PASTA).

## Remediation Tasks

- RT-SBOM-001: Add `anchore/sbom-action` or `anchore/syft` to CI to generate SBOM on build.
- RT-VEX-001: Configure Trivy to output VEX or manually maintain `docs/security/vex.json`.
- RT-SLSA-001: Integrate `slsa-framework/slsa-github-generator` for provenance attestation.
- RT-TM-001: Create `docs/security/threat-model.md` with STRIDE analysis of SIP edge.

## References
- NIST SP 800-218 (SSDF v1.1)
- security_constitution.md
- .github/workflows/ci.yml
