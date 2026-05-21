# Orchestrated Implementation Plan: 014-C + 008-SG

**Orchestration**: GitNexus + OMK + Security Governance  
**Date**: 2026-05-19  
**Status**: Planning  

---

## 1. Strategic Overview

This plan coordinates the closure of two remaining active features:

| Feature | Remaining Work | Complexity | Priority |
|---|---|---|---|
| **014-C: SIP Trunk Provider Integration** | 2 tasks (Grafana dashboard, runbook update) | Low | P1 — Quick win |
| **008-SG: Security Governance** | 18 tasks across 5 phases | High | P2 — Foundation |

**Execution Order**: 014-C first (immediate value, no blockers), then 008-SG (systematic evidence production).

---

## 2. GitNexus Integration Points

| Checkpoint | Command | Trigger |
|---|---|---|
| Pre-edit impact analysis | `gitnexus_impact` on target symbols | Before modifying any file |
| Post-edit validation | `gitnexus_detect_changes` | After each wave |
| Final verification | `npx gitnexus status` | Before commit |

**Target symbols for 014-C**:
- `docker/grafana/dashboards/` (new file)
- `docs/TSiSIP-OPERATOR-RUNBOOK.md` (append section)

**Target symbols for 008-SG**:
- `docs/security/` (multiple new files)
- `scripts/` (verify-*.sh scripts)
- `.github/workflows/` (CI enhancements)

---

## 3. OMK Integration Points

| Phase | OMK Action | Purpose |
|---|---|---|
| Phase 0 | `omk_goal_create` for 014-C | Track closure |
| Phase 0 | `omk_goal_create` for 008-SG | Track evidence production |
| Per wave | `omk_write_todos` | Task breakdown per wave |
| Post-wave | `omk_evidence_add` | Evidence attachment |
| Final | `omk_goal_verify` + `omk_goal_close` | Completion gate |

---

## 4. Phase 1: Feature 017 Closure (P1)

### 1.1 T5.5 — Create Grafana Trunk Dashboard
**Files**: `docker/grafana/dashboards/trunk-dashboard.json` (new)
**GitNexus**: Verify no existing trunk dashboard
**OMK**: Todo — "Create Grafana dashboard for trunk provider metrics"
**Security Checkpoint**: Dashboard must not expose credential fields

### 1.2 T7.7 — Update Operator Runbook
**Files**: `docs/TSiSIP-OPERATOR-RUNBOOK.md` (append)
**GitNexus**: Impact on runbook structure
**OMK**: Todo — "Document trunk provider operations"
**Security Checkpoint**: Document secret rotation for trunk credentials

---

## 5. Phase 2: Feature 008-SG Evidence Production (P2)

### 5.1 SG-1: MSL Applicability & Justification
**Deliverables**:
- `docs/security/008-MSL-applicability-justification.md` (update from draft)
- Residual Risk Register with 5 declared risks

**Security Governance Checkpoint**: MSL matrix reviewed, no `[TBD]` placeholders

### 5.2 SG-2: Infrastructure Quality Remediation
**Deliverables**:
- SG2.1: Harmonize backup metrics exporter binding
- SG2.2: CI-native nginx validation (container-based)
- SG2.3: CI-native Ansible syntax-check (container-based)

**Security Governance Checkpoint**: All 3 open `infra-quality.md` items resolved

### 5.3 SG-3: Security Controls Evidence
**Deliverables** (6 scripts + evidence):
- SG3.1: SSL Labs scan evidence
- SG3.2: Trivy CVE scan artifact in CI
- SG3.3: `scripts/verify-network-isolation.sh`
- SG3.4: `scripts/verify-secrets-audit.sh`
- SG3.5: `scripts/verify-nginx-tls.sh`
- SG3.6: `scripts/verify-health-checks.sh`

**Security Governance Checkpoint**: All scripts pass in CI, evidence archived

### 5.4 SG-4: Operational Security
**Deliverables**:
- SG4.1: `scripts/secret-age-audit.sh` (90-day rotation warning)
- SG4.2: Deterministic image pinning policy
- SG4.3: SIP exposure decision document
- SG4.4: Incident response runbook

**Security Governance Checkpoint**: Runbook contains no `[TBD]`, review date set

### 5.5 SG-5: Finalization
**Deliverables**:
- SG5.1: Evidence index completed
- SG5.2: Feature 008 spec status updated
- SG5.3: Final sign-off with zero hard failures

**Security Governance Checkpoint**: Evidence index is current, CI scan passes

---

## 6. Security Review Checkpoints (Security Governance Preset)

| Checkpoint | Applies To | Verification |
|---|---|---|
| MSL Applicability | 008-SG SG-1 | Matrix complete with justification |
| Secure Dev Verification | 008-SG SG-3 | Scripts produce falsifiable evidence |
| Supply-Chain Evidence | 008-SG SG-3.2, SG-4.2 | Trivy artifacts, pinned images |
| Dependency Evidence | 008-SG SG-2.2, SG-2.3 | CI validation without host binaries |
| Secret Hygiene | 008-SG SG-3.4, SG-4.1 | Audit script, age warning |
| Incident Readiness | 008-SG SG-4.4 | Runbook reviewed |

---

## 7. Execution Order

```
Phase 1 (014-C)
  └─> T5.5 (Grafana dashboard)
  └─> T7.7 (Runbook update)
  └─> Commit 014-C closure

Phase 2 (008-SG)
  SG-1: MSL & Risk Register
  SG-2: Infra Quality Scripts
  SG-3: Security Evidence Scripts
  SG-4: Operational Security Docs
  SG-5: Finalization & Sign-off
  └─> Commit 008-SG completion
```

---

## 8. Rollback Plan

- If 014-C T5.5 fails: Skip dashboard, document limitation in runbook
- If 008-SG script fails in CI: Mark as non-blocking, document workaround
- If MSL matrix is rejected: Iterate with reviewer, update justification

---

*Plan generated by speckit-plan with Security Governance preset.*
