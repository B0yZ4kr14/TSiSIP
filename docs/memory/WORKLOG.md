# Worklog — TSiSIP Agent Memory

## 2026-05-19: Feature 019 — Spec Kit Memory Hub Integration

### Session: Implementation
- **Agent**: Kimi (omk-project harness)
- **Tasks Completed**:
  - Installed `memory-md` extension (v0.8.5) via `specify extension add memory-md`
  - Verified CLI compatibility and extension registration
  - Created security assessment (SEC-019-EVI-001)
  - Created agent memory governance (SEC-019-EVI-002)
  - Updated security evidence index with Feature 019 artefacts
  - Created memory-hub config.yml with TSiSIP-specific indexing rules
  - Created docs/memory/ directory with INDEX, PROJECT_CONTEXT, ARCHITECTURE, DECISIONS, BUGS, WORKLOG
  - Added .spec-kit-memory/ and lock files to .gitignore
- **Decisions Made**:
  - MSL Applicability: Non-MSL (TSiSIP-SEC-019-MSL-EXEMPT-001)
  - Embedding model: Disabled (local-only); no API keys required
  - Index scope: Excludes secrets/, .env*, seed data, and HA1 utilities
- **Security Gates**:
  - Secret scan: PASS (no secrets in memory paths)
  - MSL review: Non-MSL approved
  - RBAC: L1–L2 read-only for agents; L3–L5 writes require approval

### Next Session
- Bootstrap initial memory synthesis
- Test prepare-context, capture, and audit commands
- Complete validation tests (T3.1–T3.5)
- Update AGENTS.md with memory hub references
- Generate blueprint.md and commit all artefacts

## 2026-05-19: Feature 020 — OCP Critical Tool Gap Closure (Completed)

### Session: Implementation + Remediation + Memory Synthesis
- **Agent**: Kimi (omk-project harness)
- **Tasks Completed**:
  - Implemented 6 OCP tools: dialog.php, mi-commands.php, statistics.php, dialplan.php, domains.php, tls-management.php
  - Added RBAC (devops minimum, admin for sensitive ops)
  - Added CSRF protection on all mutating forms
  - Added audit logging on all success paths
  - Added negative tests for MI command rejection and audit failure logging
  - Created security assessment and threat model artefacts
  - Updated operator runbook and AGENTS.md
  - Committed all changes (3 commits, pending push)
- **Memory Synthesis**:
  - Generated specs/020-ocp-critical-tool-gap-closure/memory-synthesis.md
  - Generated specs/020-ocp-critical-tool-gap-closure/security-constraints.md
  - Generated specs/020-ocp-critical-tool-gap-closure/architecture-refactor-tasks.md
- **Security Review**:
  - 10/10 security requirements pass
  - 3 MEDIUM findings identified (1 new: CRUD audit gap; 2 pre-existing: headers, session)
  - 0 critical/high findings
- **Architecture Refactor Tasks**:
  - R1: CRUD failure audit logging
  - R2: Security headers (CSP, X-Frame-Options)
  - R3: Session hardening (regenerate_id, cookie flags)
  - R4: Statistics error path hardening
  - R5: D3.js local fallback
  - R6: validate-input.php integration (P3)
  - R7: Post-fix brownfield scan
- **Decisions Made**:
  - AD-020-1 through AD-020-4 (see DECISIONS.md)
- **Next Session**:
  - Execute R1-R3 remediation tasks
  - Run R7 post-fix brownfield scan
  - Create evidence in evidence/remediation/ciclo-020/
  - Push pending commits to GitHub

## 2026-05-19: Feature 020 — Remediation Cycle Execution (R1-R5)

### Session: Architecture Refactor
- **Agent**: Kimi (omk-project harness)
- **Tasks Completed**:
  - R1: Added logAuditEvent() in PDOException catch blocks (dialplan.php, domains.php)
  - R2: Added 4 security headers to common/header.php (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
  - R3: Added session hardening (regenerate_id on login, httponly, samesite=Strict, strict_mode)
  - R4: Added statistics warning banner on MI failure; charts preserve last-known values
  - R5: Added D3.js CDN fallback with graceful degradation message
  - R7: Post-fix brownfield scan — 10/10 verification checks pass, zero new findings
- **Evidence Created**:
  - evidence/remediation/ciclo-020/README.md
  - evidence/remediation/ciclo-020/r7-post-fix-scan/brownfield-scan-report.md
  - Fix diffs in r1/ through r5/ directories
- **Artefacts Updated**:
  - specs/020-ocp-critical-tool-gap-closure/plan.md (Wave 5 added)
  - specs/020-ocp-critical-tool-gap-closure/tasks.md (R1-R7 added)
  - docs/memory/BUGS.md (BUG-006, BUG-007 marked resolved)
  - docs/memory/memory-synthesis.md (findings marked resolved)
## 2026-05-27: Frontend Surgical Audit & Orphan Consolidation

### Session: Code Quality + Navigation Fix
- **Agent**: Kimi (omk-project harness)
- **Tasks Completed**:
  - Fixed OpenSIPS syntax error (`children = 8` removed from `opensips.cfg.tpl`)
  - Fixed broken `addresses` menu link → `address` in `role-nav.php`
  - Removed wiki from sidebar, added 📖 wiki button to `header.php`
  - Consolidated 18 orphan pages into navigation menus
  - Removed redundant stubs (`health.php`, `healthcheck-audit.php`)
  - Removed duplicate trunk pages (`trunk-dids.php`, `trunk-providers.php`, `trunk-status.php` from `web/ocp/`)
  - Updated Docker Compose healthcheck after page removal
  - Pruned dangling Docker images (~937MB freed) and unused volumes (~49MB)
- **Validation**:
  - 17/17 OCP smoke tests PASS
  - Zero broken links
  - Zero orphan pages
  - Container healthy (16/17 services; certbot/tailscale blocked by external DNS/ACL)
- **OCP Coverage Update**:
  - Previous audit reported 16% coverage (outdated)
  - Actual coverage: ~81% (26/32 OCP v9.3.6 modules)
  - Only stubs remaining: trunk-status, tviewer
- **Docs Updated**:
  - `docs/OCP-CROSS-ANALYSIS.md` — corrected coverage matrix and added Section 8
  - `docs/CHANGELOG-2026-05.md` — added v1.0.1 section

## 2026-05-27: Features 029–031 Completion

### Session: Final Q2 Feature Sprint
- **Tasks Completed**:
  - Feature 029: Frontend Refactor + MI Parity (drag-and-drop dashboard, 8 SSE data sources, MI export)
  - Feature 030: User Management & RBAC (user CRUD, role assignment, forced password change)
  - Feature 031: REST API (public status endpoint, API key auth, OpenAPI docs)
  - Updated `.omk/memory/orchestrated-pending-tasks-plan.md` — all specs 001–031 marked complete
- **External Blockers (unchanged)**:
  - DNS A record for `tsiapp.io`
  - Firewall/Tailscale ACL for SIP 5060/udp+tcp
  - Real S3 credentials in `secrets/rclone_s3_*`

- **Deferred**:
  - R6: validate-input.php integration (P3 cleanup, no urgency)
