# Security Evidence Index — TSiSIP

**Date**: 2026-05-23
**Version**: 1.0.0

---

## Feature Evidence

| Feature | Directory | Artifacts | Status |
|---|---|---|---|
| 022 — VPS Go-Live | `evidence/022-vps-go-live/` | 17 | In Progress |
| 023 — Subscriber CRUD Refactor | `evidence/023-subscriber-crud-refactor/` | [TBD] | Planned |

## 022 Evidence Detail

| File | Evidence Type | Status | Notes |
|------|--------------|--------|-------|
| `001-tls-certificate-scan.md` | G5 + G9 | BLOCKED | DNS A record pending |
| `002-container-image-scan.md` | G6 | REVIEW | Baseline: 31 CRITICAL, 304 HIGH |
| `002-container-image-scan-v2.md` | G6 | REVIEW | 7 images rebuilt: 17 CRITICAL, 263 HIGH |
| `002-container-image-scan-v3.md` | G6 | REVIEW | All 8 images rebuilt: 19 CRITICAL, 276 HIGH |
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
