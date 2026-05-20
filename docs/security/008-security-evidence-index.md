# Security Evidence Index — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-EVI-001  
**Date**: 2026-05-19  
**Status**: Draft — awaiting evidence production from SG-phase tasks  
**Next review**: 2026-08-17  

---

## 1. Evidence Inventory

| Evidence ID | Description | Location | Produced By | Date | Expires | Next Review |
|---|---|---|---|---|---|---|
| EV-001 | SSL Labs TLS grade report | `docs/security/evidence/008-ssl-labs-grade-*.html` | SG3.1 | — | 90 days | — |
| EV-002 | Container image CVE scan (latest) | `docs/security/evidence/008-trivy-scan-latest.json` | SG3.2 | — | Per release | — |
| EV-003 | Network isolation verification | `docs/security/evidence/008-network-isolation-*.txt` | SG3.3 | — | 90 days | — |
| EV-004 | Secret management audit | `docs/security/evidence/008-secrets-audit-*.txt` | SG3.4 | — | 90 days | — |
| EV-005 | Nginx TLS configuration audit | `docs/security/evidence/008-nginx-tls-*.txt` | SG3.5 | — | 90 days | — |
| EV-006 | Health check validation | `docs/security/evidence/008-health-checks-*.txt` | SG3.6 | — | 90 days | — |
| EV-007 | MSL applicability justification | `docs/security/008-MSL-applicability-justification.md` | SG1.1 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-008 | Residual risk register | `docs/security/008-MSL-applicability-justification.md` | SG1.2 | 2026-05-19 | 90 days | 2026-08-17 |
| EV-009 | Secret rotation procedures | `docs/security/008-secret-rotation-procedures.md` | SG4.1 | — | 90 days | — |
| EV-010 | Image pinning policy | `docs/security/008-image-pinning-policy.md` | SG4.2 | — | 90 days | — |
| EV-011 | SIP exposure decision | `docs/security/008-sip-exposure-decision.md` | SG4.3 | — | 90 days | — |
| EV-012 | Incident response runbook | `docs/security/008-incident-response-runbook.md` | SG4.4 | — | 90 days | — |

---

## 2. Evidence Production Status

| Phase | Tasks | Total | Complete | Pending |
|---|---|---|---|---|
| SG-1: MSL Applicability | SG1.1, SG1.2 | 2 | 0 | 2 |
| SG-2: Infra Quality | SG2.1, SG2.2, SG2.3 | 3 | 0 | 3 |
| SG-3: Security Controls | SG3.1–SG3.6 | 6 | 0 | 6 |
| SG-4: Operational Security | SG4.1–SG4.4 | 4 | 0 | 4 |
| SG-5: Finalization | SG5.1–SG5.3 | 3 | 0 | 3 |
| **Total** | | **18** | **0** | **18** |

---

## 3. Sign-off

> **Not yet signed off.** This section will be completed under SG5.3.

| Role | Name | Date | Status |
|---|---|---|---|
| Evidence Producer | — | — | Pending |
| Independent Reviewer | — | — | Pending |
| Security Owner | — | — | Pending |
