# TSiSIP Merged Features Log

> Archive of completed feature specifications and session runs.
> Version: 1.0.0 | Last Updated: 2026-05-24

---

## Session Run — 2026-05-24

**Feature**: Feature 020: OCP Critical Tool Gap Closure
**Branch**: master (merged)
**Spec**: specs/020-ocp-critical-tool-gap-closure

### Session Metadata

| Metric | Value |
|---|---|
| Speckit skills executed | 9 |
| Commits | 6 |
| Production deploy | VPS tsiapp.io |
| OpenSIPS image rebuild | Yes (canon-drift fixes) |
| OCP image rebuild | Yes (i18n + MI HTTP integration) |
| CI scan status | Pass |

### Commits

- `d76c052` chore(evidence): add missing security evidence files for spec 008
- `af5b782` feat(mi-http): expand real-time status to Call Center, Load Balancer, RTPengine
- `bf303f8` fix(ci): improve test resilience and audit accuracy
- `1610f81` feat(mi-http): OpenSIPS MI HTTP integration for real-time status modules
- `67811f8` feat(i18n): add EN/ES/PT gettext strings for all 16 new OCP modules
- `6180e46` fix(opensips): canon-drift remediation - reply route, failure route, ICE=remove, persistent_state

### What was added

- Six OCP administrative tools (dialog, mi-commands, statistics, dialplan, domains, tls-management)
- Reusable MI HTTP helper (web/common/mi-http.php)
- 351 i18n msgid entries across EN/ES/PT locales
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- Session hardening (HttpOnly, SameSite=Strict, strict_mode)
- Real-time status modules via MI HTTP (clusterer, siptrace, status-report, sockets-management, call-center, load-balancer, rtpengine)
- Canon-drift remediation for OpenSIPS config (reply route, failure route, ICE=remove, persistent_state)
- Security evidence for Feature 008 (SSL Labs, Trivy scan, blueprint validation)

### New Components

- web/dialog.php
- web/mi-commands.php
- web/statistics.php
- web/dialplan.php
- web/domains.php
- web/tls-management.php
- web/common/mi-http.php
- web/clusterer.php
- web/siptrace.php
- web/status-report.php
- web/sockets-management.php
- web/call-center.php
- web/load-balancer.php
- web/rtpengine.php (real-time status)

### Tasks Completed

61/61 tasks (Feature 020)

---

## Feature 020 — OCP Critical Tool Gap Closure

**Date**: 2026-05-24
**Branch**: feature-020-ocp-critical-tool-gap-closure
**Spec**: specs/020-ocp-critical-tool-gap-closure

### What was added

- Dialog Viewer: read-only view of active SIP dialogs
- MI Commands Runner: whitelisted OpenSIPS MI command proxy
- Statistics Monitor: D3.js dashboard with 6+ metrics and 30s auto-refresh
- Dialplan Manager: full CRUD on PostgreSQL dialplan table
- Domains Manager: full CRUD on PostgreSQL domain table
- TLS Management: certificate status viewer and tls_reload trigger

### New Components

- web/dialog.php
- web/mi-commands.php
- web/statistics.php
- web/dialplan.php
- web/domains.php
- web/tls-management.php
- web/common/mi-http.php
- docs/security/020-ocp-gap-closure-security-assessment.md
- docs/security/020-ocp-gap-closure-threat-model.md

### Tasks Completed

61/61 tasks

---

## Session Run — 2026-05-27

**Feature**: Feature 020: OCP Critical Tool Gap Closure (Final Validation + Frontend Audit)
**Branch**: main
**Spec**: specs/020-ocp-critical-tool-gap-closure

### Session Metadata

| Metric | Value |
|---|---|
| Speckit skills executed | 2 (speckit-implement, speckit-brownfield-scan) |
| GitNexus reindex | Completed (9,930 nodes, 10,912 edges, 81 clusters) |
| Frontend audit | Completed — 17/17 tests PASS |
| Commits since last session | 4 |

### Commits

- `3738baf` fix(docker): update OCP healthcheck after removing healthcheck-audit.php
- `77e5d95` feat(frontend): consolidate orphan pages, remove duplicates, clean menu
- `a202349` fix(frontend): remove wiki from sidebar, add header button, fix addresses link
- `a27a55b` fix(opensips): remove children directive causing syntax error in 3.6.6

### What was validated

- Feature 020: 61/61 tasks complete, 100% coverage, 0 placeholders, 0 constitution violations
- OCP frontend: 17/17 smoke tests PASS, zero broken links, zero orphan pages
- GitNexus index refreshed (incremental: +239 files, -4 deleted, 77 changed)
- Docker cleanup: ~937MB images + ~49MB volumes pruned

### OCP Coverage Update

| Category | OCP Tools | TSiSIP Implemented | Coverage % |
|---|---|---|---|
| Global Config | 4 | 0 | 0% |
| Dashboard | 1 | 1 | 100% |
| SIP Users | 3 | 3 | 100% |
| System (Core) | 23 | 22 | 96% |
| Generic | 1 | 0 | 0% |
| **OCP Total** | **32** | **26** | **81%** |
