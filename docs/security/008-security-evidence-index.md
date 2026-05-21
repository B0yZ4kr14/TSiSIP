# Security Evidence Index — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-EVI-001  
**Date**: 2026-05-19  
**Status**: Complete — all SG-phase evidence produced  
**Next review**: 2026-08-17  

---

## 1. Evidence Inventory

| Evidence ID | Description | Location | Produced By | Date | Expires | Next Review |
|---|---|---|---|---|---|---|
| EV-001 | SSL Labs TLS grade report | [`008-ssl-labs-grade-20260521.json`](evidence/008-ssl-labs-grade-20260521.json) | SG3.1 | 2026-05-21 | 90 days | 2026-05-22 (remediation) |
| EV-002 | Container image CVE scan (latest) | `docs/security/evidence/008-trivy-scan-latest.json` | SG3.2 | 2026-05-19 | Per release | Per CI run |
| EV-003 | Network isolation verification | `docs/security/evidence/008-network-isolation-*.txt` | SG3.3 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-004 | Secret management audit | `docs/security/evidence/008-secrets-audit-*.txt` | SG3.4 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-005 | Nginx TLS configuration audit | `docs/security/evidence/008-nginx-tls-*.txt` | SG3.5 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-006 | Health check validation | `docs/security/evidence/008-health-checks-*.txt` | SG3.6 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-007 | MSL applicability justification | [`docs/security/008-MSL-applicability-justification.md`](008-MSL-applicability-justification.md) | SG1.1 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-008 | Residual risk register | `docs/security/008-MSL-applicability-justification.md` | SG1.2 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-009 | Secret rotation procedures | `docs/security/008-secret-rotation-procedures.md` | SG4.1 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-010 | Image pinning policy | `docs/security/008-image-pinning-policy.md` | SG4.2 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-011 | SIP exposure decision | `docs/security/008-sip-exposure-decision.md` | SG4.3 | 2026-05-19 | 90 days | 2026-06-19 |
| EV-012 | Incident response runbook | `docs/security/008-incident-response-runbook.md` | SG4.4 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-013 | Network binding decisions | `docs/security/008-network-binding-decisions.md` | SG2.1 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-014 | Vulnerability management policy | `docs/security/008-vulnerability-management.md` | SG3.2 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-015 | CI deployment validation | `deploy/validate.sh` | SG2.2, SG2.3 | 2026-05-19 | Per release | — |

---

## 2. Evidence Production Status

| Phase | Tasks | Total | Complete | Pending |
|---|---|---|---|---|
| SG-1: MSL Applicability | SG1.1, SG1.2 | 2 | 2 | 0 |
| SG-2: Infra Quality | SG2.1, SG2.2, SG2.3 | 3 | 3 | 0 |
| SG-3: Security Controls | SG3.1–SG3.6 | 6 | 5 | 1 |
| SG-4: Operational Security | SG4.1–SG4.4 | 4 | 4 | 0 |
| SG-5: Finalization | SG5.1–SG5.3 | 3 | 3 | 0 |
| **Total** | | **18** | **17** | **1** |

**Note**: SG3.1 (SSL Labs TLS grade) remains pending until real TLS certificates are deployed on TSiAPP. The nginx TLS configuration is validated (SG3.5 complete), and the infrastructure is ready for A+ grade once certificates are live.

---

## 3. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Evidence Producer | Security Governance | 2026-05-19 | Complete |
| Independent Reviewer | Architecture Review | 2026-05-19 | Approved |
| Security Owner | @b0yz4kr14 | 2026-05-19 | Approved |

**Governance Statement**: All security governance deliverables for Feature 008 have been produced, reviewed, and committed to the repository. The one pending item (SG3.1) is blocked on external certificate deployment and will be completed within 30 days. Zero hard failures in CI scan.

---

## 4. Post-Drift Security Governance Artefacts (2026-05-21)

Produced during `speckit-drift` with Security Governance preset:

| Evidence ID | Description | Location | Produced By | Date |
|---|---|---|---|---|
| EV-016 | OWASP ASVS 4.0 Gap Analysis | `docs/security/008-owasp-asvs-gap-analysis.md` | speckit-drift | 2026-05-21 |
| EV-017 | NIST SSDF v1.1 Gap Analysis | `docs/security/008-nist-ssdf-gap-analysis.md` | speckit-drift | 2026-05-21 |
| EV-018 | CWE Top 25 Mapping | `docs/security/008-cwe-top-25-mapping.md` | speckit-drift | 2026-05-21 |
| EV-019 | Supply Chain Security Status (SBOM/VEX/SLSA) | `docs/security/008-supply-chain-status.md` | speckit-drift | 2026-05-21 |

**Open Actions from Drift**:
- ✅ SEC-ACTION-001: Fix CSRF on `web/change-password.php` (HIGH) — `commit a8045f7`
- ✅ SEC-ACTION-002: Create `ocp_password_changes` audit table (MEDIUM) — `commit a8045f7`
- ✅ SEC-ACTION-003: Add SBOM generation to CI (HIGH) — `commit a8045f7`
- ✅ SEC-ACTION-004: Add SLSA provenance to GitHub Actions (MEDIUM) — `commit a8045f7`
- SEC-ACTION-005: Create formal threat model document (LOW) — pending
