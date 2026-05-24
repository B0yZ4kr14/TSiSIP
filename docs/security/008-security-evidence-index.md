# Security Evidence Index — TSiSIP

**Date**: 2026-05-23
**Version**: 1.0.0

---

## Feature Evidence

| Feature | Directory | Artifacts | Status |
|---|---|---|---|
| 022 — VPS Go-Live | `evidence/022-vps-go-live/` | 17 | In Progress |

## 022 Evidence Detail

| File | Evidence Type | Status | Notes |
|------|--------------|--------|-------|
| `001-tls-certificate-scan.md` | G5 + G9 | BLOCKED | DNS A record pending |
| `002-container-image-scan.md` | G6 | REVIEW | 31 CRITICAL, 304 HIGH vulns found |
| `004-network-segmentation-test.md` | G7 | PASS | Zero public Asterisk/PostgreSQL ports |
| `005-secret-management-audit.md` | G8 | PASS | HA1 precomputed, no plaintext passwords |
| 021 — Brownfield Hardening | `evidence/021-brownfield/` | [TBD] | Planned |
| 008 — DevSecOps | `evidence/008-devsecops/` | [TBD] | Planned |

## Governance Documents

| Document | Purpose | Version |
|---|---|---|
| `008-MSL-applicability-justification.md` | LGPD/MSL legal basis | 1.0.0 |
| `008-stride-threat-model.md` | Threat analysis | 1.0.0 |
| `008-secure-deployment-checklist.md` | Deployment security | 1.0.0 |
| `008-incident-response-runbook.md` | Incident procedures | 1.0.0 |
| `008-secret-rotation-procedures.md` | Secret lifecycle | 1.0.0 |
| `008-security-evidence-index.md` | This index | 1.0.0 |

## Evidence Retention

- Security evidence: 7 years (aligned with CDR retention)
- Incident evidence: 7 years
- Scan reports: 2 years
- Audit logs: 1 year

**Next Review**: 2026-11-23
