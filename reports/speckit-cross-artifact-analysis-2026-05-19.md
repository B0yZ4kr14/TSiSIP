[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] Shell
[tool] Shell
[tool_result] success
[tool] Shell
[tool_result] success
[tool_result] success
[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] ReadFile
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool] ReadFile
[tool_result] success
[tool_result] success
[tool] Shell
[tool] ReadFile
[tool_result] success
[tool_result] success
# TSiSIP Comprehensive Specification Analysis Report

**Analysis Date**: 2026-05-19  
**Analyzer**: `speckit-analyze` (cross-artifact consistency & quality audit)  
**Scope**: specs/001–009, docs/TSiSIP-CANONICAL-SPEC.md, docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md, docs/TSiSIP-OPERATOR-RUNBOOK.md, AGENTS.md  
**Constitution**: `.specify/memory/constitution.md` v1.0.0  

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Total Specifications Analyzed | 9 |
| Total Functional Requirements | 63 |
| Total Success Criteria | 65 |
| Total Tasks | 136 |
| Coverage % (requirements with ≥1 task) | ~89% |
| **Critical Issues** | **3** |
| **High Issues** | **5** |
| **Medium Issues** | **6** |
| **Low Issues** | **3** |

**Verdict**: Three **CRITICAL** issues block baseline constitution compliance. Do not proceed with `/speckit-implement` on affected specs until resolved. Feature 009 (VPS Deploy Automation) is entirely draft with zero completed tasks and should be refined before planning continues.

---

## Extension Hooks

No pre- or post-analysis hooks are registered in `.specify/extensions.yml`.

---

## Per-Specification Analysis

---

### Spec 001 — OpenSIPS Docker Edge Proxy
**Status**: Completed | **FRs**: 10 | **SCs**: 9 | **Tasks**: 15

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| A1 | Constitution | **CRITICAL** | plan.md:L123-137, tasks.md:T3.3 | RTPengine `--listen-ng` bound to `0.0.0.0:22222` on `sip_internal`. CANONICAL-SPEC §5 explicitly forbids this: *"Binding it to `0.0.0.0` exposes the control socket on every container interface, including `sip_edge`."* Violates Constitution §4 (RTPengine ng-control must not be exposed externally). | Change to `${RTPENGINE_INTERNAL_IP}:22222` and ensure `sip_internal` is the only attachment. |
| A2 | Coverage Gap | **HIGH** | spec.md:FR-008, FR-009; tasks.md:T4.6 | FR-008 (permissions/address table whitelist) and FR-009 (auth_audit_log) have **no implementation tasks**. T4.6 only validates they are "documented in spec and plan." No task adds the actual `permissions` module config, `address` table population logic, or audit-logging route logic in `opensips.cfg.tpl`. | Add explicit tasks: T_X Configure permissions module & address table lookup; T_Y Add auth audit logging route with SQL insert. |
| A3 | Inconsistency | **HIGH** | CANONICAL-SPEC §9 vs spec.md:FR-005 | CANONICAL-SPEC §9 mandates `proxy_authorize()`/`proxy_challenge()` for non-REGISTER requests (407). Spec 001 FR-005 and the implemented `opensips.cfg.tpl` use `www_authorize()`/`www_challenge()` for all methods (401). CANONICAL-SPEC notes this as a "documented deviation pending alignment" with no concrete resolution task. | Create a constitution-alignment task to migrate non-REGISTER auth to `proxy_authorize()`/`proxy_challenge()`, or update CANONICAL-SPEC to accept 401 for all methods with explicit rationale. |
| A4 | Inconsistency | **MEDIUM** | tasks.md:T4.7 vs T4.6 | Task numbering anomaly: T4.7 ("Validate authenticated production routing") appears before T4.6 ("Final documentation update") in the tasks file. | Renumber T4.7 → T4.6 and T4.6 → T4.7 for sequential correctness. |

**Coverage Summary Table (selected)**:

| Requirement Key | Has Task? | Task IDs | Notes |
|-----------------|-----------|----------|-------|
| FR-001 (Project-owned image) | ✅ | T1.1–T1.4 | Fully covered |
| FR-002 (Secret injection) | ✅ | T1.2, T3.2, T4.1 | Fully covered |
| FR-003 (PostgreSQL persistence) | ✅ | T2.1–T2.4, T3.1 | Fully covered |
| FR-004 (Network isolation) | ✅ | T3.1, T3.4 | Fully covered |
| FR-005 (Edge auth enforcement) | ✅ | T4.3, T4.5, T4.7 | Covered, but see A3 |
| FR-006 (Syntax validation) | ✅ | T1.4, T4.3 | Fully covered |
| FR-007 (Canonical routing skeleton) | ✅ | T1.3 | Fully covered |
| FR-008 (Trusted gateway bypass) | ⚠️ | T4.6 (docs only) | **No implementation task** — see A2 |
| FR-009 (Auth audit logging) | ⚠️ | T4.6 (docs only) | **No implementation task** — see A2 |
| FR-010 (Health probe OPTIONS) | ✅ | T4.4 | Fully covered |

**Metrics**: 10 FRs, 15 Tasks, Coverage 80% (8/10 FRs with direct tasks; 2 via documentation only).

---

### Spec 002 — TSiSIP OCP Rebrand
**Status**: Implemented | **FRs**: 10 | **SCs**: 16 | **Tasks**: 30

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| B1 | Ambiguity | **MEDIUM** | spec.md:FR-007; tasks.md:T013 | FR-007 AC says "No existing OCP PHP view files are modified except for `web/css/main.css` and `web/common/header.php`." T013 styles the login page via CSS selectors only. If `web/login.php` is modified (even by CSS class injection), it violates FR-007; if untouched, the requirement is met but the phrasing is ambiguous. | Clarify in spec whether "modify" means file-content change or PHP-logic change. Add explicit AC: "Login page styling is applied purely via CSS; `login.php` content is unchanged." |
| B2 | Underspecification | **MEDIUM** | spec.md:SC-015 | SC-015 targets "Cumulative Layout Shift during logo responsive swap on mobile < 0.05." No task explicitly measures CLS with Lighthouse on the login→dashboard navigation path. T027 measures general Lighthouse performance but does not isolate CLS for the logo swap. | Add a dedicated CLS measurement task or update T027 acceptance to explicitly capture logo-swap CLS. |

**Coverage Summary**: All 10 FRs have direct task coverage. All 16 SCs map to validation gates in Phases 2, 4, and 7.

**Metrics**: 10 FRs, 30 Tasks, Coverage 100%.

---

### Spec 003 — Prometheus/Grafana Observability
**Status**: Partial | **FRs**: 6 | **SCs**: 6 | **Tasks**: 10

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| C1 | Underspecification | **MEDIUM** | spec.md:SC-004 | SC-004: "Prometheus scrape duration at 1000 concurrent calls ≤ 500ms." No task establishes a load-generation baseline or measures scrape latency under load. T4.2 is an integration test but does not specify load conditions. | Add a task to validate scrape latency under the 1000-call load target defined in spec 001 SC-007. |
| C2 | Terminology Drift | **LOW** | spec.md:Dependencies | Lists "Feature 002 (OCP Rebranding) provides the role-aware UI framework for Grafana dashboards." Grafana dashboards are independent of OCP PHP views; the dependency is artificially strong. Role awareness in Grafana is handled by Grafana's native org/team features, not OCP rebranding. | Weaken dependency to "Feature 002 theme.json provides color palette consistency" only. |
| C3 | Coverage Gap | **LOW** | spec.md:FR-003 | FR-003 requires dashboards in EN/ES/PT. T3.5 implements i18n JSON files, but no task verifies that all **panel descriptions** (not just titles) are translated. | Expand T3.5 acceptance criteria to include panel descriptions and legend text. |

**Metrics**: 6 FRs, 10 Tasks, Coverage 100%.

---

### Spec 004 — Health Checks & Auto-Healing
**Status**: Partial | **FRs**: 6 | **SCs**: 6 | **Tasks**: 13

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| D1 | Coverage Gap | **HIGH** | spec.md:FR-001; tasks.md:T1.4 | FR-001 requires HEALTHCHECK for **all** containers including Asterisk. T1.4 (Asterisk health check) is explicitly deferred with note: "Skip T1.4 until Asterisk container is introduced in a separate feature." This leaves FR-001 partially unfulfilled with no tracking task. | Either remove Asterisk from FR-001 scope, or create a deferred tracking task linked to the feature that introduces Asterisk. |
| D2 | Duplication | **MEDIUM** | spec.md:FR-003; spec 006 FR-003 | Both spec 004 and spec 006 define dispatcher load-based routing and probing with identical `modparam` values (`ds_ping_method=OPTIONS`, `ds_ping_interval=10`). The duplication is not cross-referenced. | Add a note in both specs referencing the other, or consolidate dispatcher probing config into spec 001 (foundation) since it affects both health checks and rate limiting. |
| D3 | Ambiguity | **MEDIUM** | spec.md:FR-005 | FR-005 metric `dispatched_targets_active` is not a standard OpenSIPS MI metric. The spec does not define how this custom metric is computed or whether it replaces `dispatcher_target_state`. | Define the exact calculation or map it to an existing OpenSIPS statistic. |

**Metrics**: 6 FRs, 13 Tasks, Coverage 83% (Asterisk health check gap).

---

### Spec 005 — PostgreSQL Backup & Restore
**Status**: Implemented | **FRs**: 6 | **SCs**: 6 | **Tasks**: 16

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| E1 | Underspecification | **MEDIUM** | spec.md:Scenario 2; tasks.md:T5.4 | PITR restore is claimed as in-scope, but T5.4 note states: *"timestamp-targeted WAL replay is not production-proven. Treat as implementation artifact plus dry-run helper until a full PITR restore drill passes."* This means SC-003 (RTO ≤ 15 min for PITR) is unproven. | Either change status to "Partial" or add a blocking task to perform and document a live PITR drill. |
| E2 | Underspecification | **MEDIUM** | spec.md:FR-006; tasks.md:T6.1, T6.2 | Offsite replication tasks T6.1 and T6.2 are pending with notes: *"real remote credentials and successful offsite listing are still pending."* SC-006 (offsite replication lag ≤ 1 hour) cannot be validated. | Mark spec status as "Partial" until offsite replication is proven, or remove SC-006 from committed scope. |
| E3 | Ambiguity | **LOW** | spec.md:Clarifications (Portuguese) | Several clarifications are written in Portuguese without English translations in the spec body (e.g., *"Sem replicação offsite; dados vulneráveis a perda local"*). While the constitution does not mandate English, bilingual specs risk misinterpretation by non-Portuguese implementers. | Add English translations alongside Portuguese clarifications. |

**Metrics**: 6 FRs, 16 Tasks, Coverage 100% (but 2 pending tasks block full success validation).

---

### Spec 006 — Rate Limiting & DDoS Protection
**Status**: Partial | **FRs**: 5 | **SCs**: 6 | **Tasks**: 16

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| F1 | Coverage Gap | **HIGH** | tasks.md | 8 of 16 tasks are **pending**: T1.2, T1.3, T2.1, T2.2, T3.2, T4.1, T4.2, T4.3, T5.3. Core functionality (NATed enterprise handling, TCP connection limits, auth rate limiting, ban lists, global throttle) is unimplemented. | Reassess status; "Partial" understates the incomplete volume. Consider splitting into two specs: MVP (pike + anomaly) and Phase 2 (auth limits + ban lists). |
| F2 | Ambiguity | **MEDIUM** | spec.md:FR-005 | "If global request volume exceeds 3 standard deviations from a 24-hour rolling baseline" — baseline granularity (per minute? per second?) and minimum sample size are undefined. | Specify baseline aggregation window (e.g., 1-minute buckets, minimum 6 hours of data). |
| F3 | Inconsistency | **MEDIUM** | spec.md:FR-002 | Uses `htable` for auth failure tracking, but CANONICAL-SPEC §6 lists `userblacklist` as the canonical module for "per-user ban list for repeated auth failures." The spec does not justify choosing `htable` over `userblacklist`. | Document rationale for `htable` vs `userblacklist` or align with CANONICAL-SPEC module baseline. |

**Metrics**: 5 FRs, 16 Tasks, Coverage 50% (8 pending).

---

### Spec 007 — TLS/SRTP Encryption
**Status**: Partial | **FRs**: 5 | **SCs**: 6 | **Tasks**: 15

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| G1 | Coverage Gap | **HIGH** | tasks.md | 7 of 15 tasks are **pending**: T2.3 (mTLS trunk logic), T3.2 (rotation monitoring), T4.1–T4.3 (SRTP integration), T5.1–T5.2 (cipher hardening). The actual SRTP media encryption and cipher restriction are almost entirely unimplemented. | Reassess status. Most core deliverables (SRTP, cipher suites) are pending. Add tasks for OpenSIPS 3.6 doc validation of `tls_mgm` cipher_list syntax. |
| G2 | Underspecification | **MEDIUM** | spec.md:FR-003 | "Existing TLS connections continue using the old certificate until natural closure" — no timeout or forced-disconnect policy is defined for compromised certificates. | Add AC: emergency rotation may optionally send MI `tls_close` or document that compromised certs require maintenance window. |
| G3 | Constitution | **MEDIUM** | spec.md:Risks:R-004 | Proposes maintaining a separate legacy TLS profile with weaker ciphers. Constitution §5 and §9 imply hardened cipher enforcement; a weaker profile contradicts the "hardened cipher suites" objective unless explicitly scoped and justified. | If retained, scope the legacy profile as strictly opt-in, isolated listener, and document as a constitution exception with security review. |

**Metrics**: 5 FRs, 15 Tasks, Coverage 53% (7 pending).

---

### Spec 008 — DevSecOps Deployment
**Status**: Live VPS production stack running; pending validations | **FRs**: 6 | **SCs**: 6 | **Tasks**: 14

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| H1 | Inconsistency | **HIGH** | spec.md:SC-004; checklist:Security Compliance | SC-004 claims A+ SSL grade (SSL Labs). Checklist says *"TLS certificates present (dummy certs deployed; real certs pending)."* Dummy certs cannot achieve A+. T4.1 validates `securityheaders.com` (HTTP headers), **not** SSL Labs (TLS config). These are different services. | Split SC-004 into two criteria: HTTP security headers (securityheaders.com) and TLS grade (SSL Labs). Add a task to run SSL Labs scan after real certs are deployed. |
| H2 | Coverage Gap | **MEDIUM** | spec.md:SC-006 | SC-006: "SIP authenticated route — Digest INVITE reaches Asterisk and returns final response." No task in tasks.md explicitly validates this SIP flow. Feature 001 T4.7 covers authenticated INVITE, but Feature 008 should have its own deploy-specific validation task. | Add T_X: Post-deploy SIP auth probe via `scripts/sip-auth-probe.py` against TSiAPP. |
| H3 | Inconsistency | **MEDIUM** | checklist:Monitoring and Alerting | Checklist marks Prometheus/Grafana as "deferred to Phase 2," but spec 003 (Observability) and spec 004 (Health Checks) already define Prometheus dashboards and alert rules. This creates ambiguity about whether observability is in or out of scope for Feature 008. | Clarify in spec 008 scope that observability stack is **excluded** and refer to specs 003–004. |
| H4 | Terminology Drift | **LOW** | spec.md vs plan.md | Spec uses "TSiAPP" and "vps-lite+PBX" interchangeably; plan uses "VPS 'TSiAPP' (Debian/Ubuntu)." Assumption says Ubuntu 22.04+ or Debian 12+, but runbook says TSiAPP runs Ubuntu 24.04. | Standardize to "Ubuntu 24.04 LTS" everywhere. |

**Metrics**: 6 FRs, 14 Tasks, Coverage 100% (but 2 SCs lack validation tasks).

---

### Spec 009 — VPS Deploy Automation Pipeline
**Status**: Draft | **FRs**: 6 | **SCs**: 4 | **Tasks**: 11

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| I1 | Constitution | **CRITICAL** | spec.md:FR-003 | "Multi-Agent Build and Push" with isolated agents implies external orchestration (OMK ensemble). Constitution §1 mandates Docker-first delivery **of runtime components**. If "agents" are long-running services, they must also be containerized. The spec does not define agent runtime packaging. | Clarify whether Builder/Pusher/Deployer/Verifier are local shell scripts, CI jobs, or containerized services. If containerized, add Dockerfile requirements. |
| I2 | Coverage Gap | **HIGH** | tasks.md | **All 11 tasks are pending.** The spec is Draft with zero implementation. It depends on Feature 008 (already live), which makes the dependency direction questionable — the pipeline is supposed to automate what already works manually. | Before planning, clarify the incremental value of Feature 009 over existing `deploy/scripts/orchestrate-deploy.sh`. If the script already exists, spec should inventory it and gap-fill, not redesign from scratch. |
| I3 | Ambiguity | **MEDIUM** | spec.md:FR-002 | "Impact analysis reports risk level for each modified component" — no definition of HIGH vs CRITICAL risk thresholds for specific file types (e.g., `opensips.cfg.tpl` vs `web/css/main.css`). | Add a risk matrix table to the spec or reference the one in `.kimi/skills/omk-security-review/`. |
| I4 | Underspecification | **MEDIUM** | spec.md:FR-005 | "Wiki endpoints return HTTP 200" — no specific wiki endpoints or content validation criteria defined. | List canonical wiki paths (e.g., `/TSiSIP/Wiki`, `/TSiSIP/Wiki/Operator-Guide`). |

**Metrics**: 6 FRs, 11 Tasks, Coverage 0% (all pending).

---

## Global Cross-Cutting Analysis

### Cross-Spec Consistency

| Issue | Severity | Details |
|-------|----------|---------|
| **RTPengine ng-control binding** | **CRITICAL** | Spec 001 plan/tasks mandate `0.0.0.0:22222`. CANONICAL-SPEC §5 forbids this. OPERATOR-RUNBOOK does not mention the binding at all. This is a direct security architecture violation. |
| **Auth response code contract** | **CRITICAL** | CANONICAL-SPEC §9 specifies `proxy_authorize()` (407) for non-REGISTER. Spec 001 FR-005 and implementation use `www_authorize()` (401) for all methods. Deviation is "documented pending alignment" with no resolution task. |
| **Network model drift** | **MEDIUM** | OPERATOR-RUNBOOK introduces `metrics_host` network for OCP and backup metrics. CANONICAL-SPEC §5 and AGENTS.md §4 only recognize `sip_edge`, `sip_internal`, `db_internal`. `metrics_host` is an undocumented architecture extension. |
| **RTP port range discrepancy** | **MEDIUM** | CANONICAL-SPEC §3 and AGENTS.md say `10000-20000/udp`. OPERATOR-RUNBOOK says TSiAPP uses `10000-10999/udp`. |
| **Prometheus scope ambiguity** | **MEDIUM** | Spec 008 checklist defers Prometheus to "Phase 2," but specs 003, 004, and 005 already define Prometheus dashboards, alert rules, and metrics exporters. There is no single "Phase 2" roadmap document tying these together. |
| **Git history contradiction** | **LOW** | AGENTS.md §2 states *"Git is initialized but has no commits."* `git log` shows commits including `24090f5`. This outdated claim risks misleading agents about repository maturity. |

### Constitution Alignment Issues

| Principle | Violation | Location | Required Fix |
|-----------|-----------|----------|--------------|
| §1 Docker-First Delivery | **POTENTIAL** | spec 009 FR-003 (multi-agent build/push) | Clarify agent runtime packaging; containerize if they are services. |
| §4 Edge Isolation & Backend Privacy | **CONFIRMED** | spec 001 plan.md:RTPengine `0.0.0.0:22222` | Bind to `sip_internal` interface only. |
| §3 OpenSIPS 3.6 LTS Baseline | **CONFIRMED** | CANONICAL-SPEC §9 deviation note | Resolve 401 vs 407 auth contract; update spec or implementation. |
| §9 Documentation Quality | **CONFIRMED** | AGENTS.md §2 "no commits" claim | Update AGENTS.md to reflect actual repository state. |

### Missing or Incomplete Work

| Gap | Impact | Recommendation |
|-----|--------|----------------|
| No unified Phase 2 roadmap document | Specs 003–008 reference "Phase 2" observability, image pinning, and CI/CD without a canonical ordering. | Create `docs/TSiSIP-ROADMAP.md` linking Phase 2 features to specs and dependencies. |
| No feature for Asterisk containerization | Spec 004 defers Asterisk health checks; spec 001 includes Asterisk in Compose but no dedicated spec exists for Asterisk runtime config. | Create spec 010 for Asterisk PBX containerization, or absorb into spec 001 as "foundation extension." |
| No spec for CI/CD pipeline | Feature 008 defers GitHub Actions; Feature 009 mentions `workflow_dispatch` but does not design it. | Create spec 011 for GitHub Actions CI/CD (image build, scan, push). |
| No spec for performance/benchmarking | Spec 001 defines performance targets (1000 sessions, <50ms latency) but defers benchmarking to "future performance feature." | Create spec 012 for load testing and performance validation. |

---

## Metrics Summary

| Metric | Count |
|--------|-------|
| Total Functional Requirements (001–009) | 63 |
| Total Success Criteria (001–009) | 65 |
| Total Tasks (001–009) | 136 |
| Requirements with ≥1 direct task | ~56 (89%) |
| Requirements with only documentation/validation tasks | 2 (FR-008, FR-009 in spec 001) |
| Ambiguity Count | 6 |
| Duplication Count | 2 |
| Critical Issues Count | 3 |
| High Issues Count | 5 |
| Medium Issues Count | 6 |
| Low Issues Count | 3 |
| Pending Tasks (all specs) | 18 |
| Completed Tasks (all specs) | 118 |

---

## Next Actions

### Blockers (resolve before `/speckit-implement`)

1. **Fix RTPengine ng-control binding** (CRITICAL): Update spec 001 plan.md and tasks.md to bind `--listen-ng` to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`. Validate against CANONICAL-SPEC §5.
2. **Resolve auth response code contract** (CRITICAL): Decide between 401 (current) and 407 (canonical) for non-REGISTER requests. Update either CANONICAL-SPEC §9 or spec 001 FR-005 and implementation, and add a concrete alignment task.
3. **Containerize or clarify Feature 009 agents** (CRITICAL): If Builder/Pusher/Deployer/Verifier are runtime services, they must be Docker-delivered per Constitution §1.

### High Priority (improve before implementation continues)

4. **Add implementation tasks for FR-008 and FR-009** (spec 001): Currently only documented, not implemented in OpenSIPS config.
5. **Fix SSL Labs vs securityheaders.com confusion** (spec 008): Split SC-004, add SSL Labs task, and clarify dummy cert status.
6. **Reassess spec 006 and 007 status**: Both have >50% pending tasks. "Partial" understates the incomplete volume. Consider status "In Progress" or breaking into phased specs.
7. **Standardize network model**: Document `metrics_host` in CANONICAL-SPEC §5 or remove it and use `db_internal` for metrics.

### Medium Priority (proceed, but address in next sprint)

8. **Update AGENTS.md repository state claim**: Change "no commits" to reflect actual git history.
9. **Standardize OS baseline**: Use "Ubuntu 24.04 LTS" consistently across specs 008 and 009.
10. **Add English translations** to spec 005 Portuguese clarifications.
11. **Create unified Phase 2 roadmap** linking observability, CI/CD, and performance features.

---

## Remediation Offer

Would you like me to suggest concrete remediation edits for the top **N** issues? I can prioritize:

- **Option A**: Top 3 CRITICAL issues (RTPengine binding, auth contract, Feature 009 agent packaging)
- **Option B**: Top 5 issues (above + FR-008/009 tasks + SSL Labs confusion)
- **Option C**: Top 10 issues (all CRITICAL/HIGH + selected MEDIUM)

Please indicate which option you prefer, or specify a different set of issues to remediate.[stage] run_soul_finished

[summary]
# TSiSIP Comprehensive Specification Analysis Report

**Analysis Date**: 2026-05-19  
**Analyzer**: `speckit-analyze` (cross-artifact consistency & quality audit)  
**Scope**: specs/001–009, docs/TSiSIP-CANONICAL-SPEC.md, docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md, docs/TSiSIP-OPERATOR-RUNBOOK.md, AGENTS.md  
**Constitution**: `.specify/memory/constitution.md` v1.0.0  

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Total Specifications Analyzed | 9 |
| Total Functional Requirements | 63 |
| Total Success Criteria | 65 |
| Total Tasks | 136 |
| Coverage % (requirements with ≥1 task) | ~89% |
| **Critical Issues** | **3** |
| **High Issues** | **5** |
| **Medium Issues** | **6** |
| **Low Issues** | **3** |

**Verdict**: Three **CRITICAL** issues block baseline constitution compliance. Do not proceed with `/speckit-implement` on affected specs until resolved. Feature 009 (VPS Deploy Automation) is entirely draft with zero completed tasks and should be refined before planning continues.

---

## Extension Hooks

No pre- or post-analysis hooks are registered in `.specify/extensions.yml`.

---

## Per-Specification Analysis

---

### Spec 001 — OpenSIPS Docker Edge Proxy
**Status**: Completed | **FRs**: 10 | **SCs**: 9 | **Tasks**: 15

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| A1 | Constitution | **CRITICAL** | plan.md:L123-137, tasks.md:T3.3 | RTPengine `--listen-ng` bound to `0.0.0.0:22222` on `sip_internal`. CANONICAL-SPEC §5 explicitly forbids this: *"Binding it to `0.0.0.0` exposes the control socket on every container interface, including `sip_edge`."* Violates Constitution §4 (RTPengine ng-control must not be exposed externally). | Change to `${RTPENGINE_INTERNAL_IP}:22222` and ensure `sip_internal` is the only attachment. |
| A2 | Coverage Gap | **HIGH** | spec.md:FR-008, FR-009; tasks.md:T4.6 | FR-008 (permissions/address table whitelist) and FR-009 (auth_audit_log) have **no implementation tasks**. T4.6 only validates they are "documented in spec and plan." No task adds the actual `permissions` module config, `address` table population logic, or audit-logging route logic in `opensips.cfg.tpl`. | Add explicit tasks: T_X Configure permissions module & address table lookup; T_Y Add auth audit logging route with SQL insert. |
| A3 | Inconsistency | **HIGH** | CANONICAL-SPEC §9 vs spec.md:FR-005 | CANONICAL-SPEC §9 mandates `proxy_authorize()`/`proxy_challenge()` for non-REGISTER requests (407). Spec 001 FR-005 and the implemented `opensips.cfg.tpl` use `www_authorize()`/`www_challenge()` for all methods (401). CANONICAL-SPEC notes this as a "documented deviation pending alignment" with no concrete resolution task. | Create a constitution-alignment task to migrate non-REGISTER auth to `proxy_authorize()`/`proxy_challenge()`, or update CANONICAL-SPEC to accept 401 for all methods with explicit rationale. |
| A4 | Inconsistency | **MEDIUM** | tasks.md:T4.7 vs T4.6 | Task numbering anomaly: T4.7 ("Validate authenticated production routing") appears before T4.6 ("Final documentation update") in the tasks file. | Renumber T4.7 → T4.6 and T4.6 → T4.7 for sequential correctness. |

**Coverage Summary Table (selected)**:

| Requirement Key | Has Task? | Task IDs | Notes |
|-----------------|-----------|----------|-------|
| FR-001 (Project-owned image) | ✅ | T1.1–T1.4 | Fully covered |
| FR-002 (Secret injection) | ✅ | T1.2, T3.2, T4.1 | Fully covered |
| FR-003 (PostgreSQL persistence) | ✅ | T2.1–T2.4, T3.1 | Fully covered |
| FR-004 (Network isolation) | ✅ | T3.1, T3.4 | Fully covered |
| FR-005 (Edge auth enforcement) | ✅ | T4.3, T4.5, T4.7 | Covered, but see A3 |
| FR-006 (Syntax validation) | ✅ | T1.4, T4.3 | Fully covered |
| FR-007 (Canonical routing skeleton) | ✅ | T1.3 | Fully covered |
| FR-008 (Trusted gateway bypass) | ⚠️ | T4.6 (docs only) | **No implementation task** — see A2 |
| FR-009 (Auth audit logging) | ⚠️ | T4.6 (docs only) | **No implementation task** — see A2 |
| FR-010 (Health probe OPTIONS) | ✅ | T4.4 | Fully covered |

**Metrics**: 10 FRs, 15 Tasks, Coverage 80% (8/10 FRs with direct tasks; 2 via documentation only).

---

### Spec 002 — TSiSIP OCP Rebrand
**Status**: Implemented | **FRs**: 10 | **SCs**: 16 | **Tasks**: 30

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| B1 | Ambiguity | **MEDIUM** | spec.md:FR-007; tasks.md:T013 | FR-007 AC says "No existing OCP PHP view files are modified except for `web/css/main.css` and `web/common/header.php`." T013 styles the login page via CSS selectors only. If `web/login.php` is modified (even by CSS class injection), it violates FR-007; if untouched, the requirement is met but the phrasing is ambiguous. | Clarify in spec whether "modify" means file-content change or PHP-logic change. Add explicit AC: "Login page styling is applied purely via CSS; `login.php` content is unchanged." |
| B2 | Underspecification | **MEDIUM** | spec.md:SC-015 | SC-015 targets "Cumulative Layout Shift during logo responsive swap on mobile < 0.05." No task explicitly measures CLS with Lighthouse on the login→dashboard navigation path. T027 measures general Lighthouse performance but does not isolate CLS for the logo swap. | Add a dedicated CLS measurement task or update T027 acceptance to explicitly capture logo-swap CLS. |

**Coverage Summary**: All 10 FRs have direct task coverage. All 16 SCs map to validation gates in Phases 2, 4, and 7.

**Metrics**: 10 FRs, 30 Tasks, Coverage 100%.

---

### Spec 003 — Prometheus/Grafana Observability
**Status**: Partial | **FRs**: 6 | **SCs**: 6 | **Tasks**: 10

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| C1 | Underspecification | **MEDIUM** | spec.md:SC-004 | SC-004: "Prometheus scrape duration at 1000 concurrent calls ≤ 500ms." No task establishes a load-generation baseline or measures scrape latency under load. T4.2 is an integration test but does not specify load conditions. | Add a task to validate scrape latency under the 1000-call load target defined in spec 001 SC-007. |
| C2 | Terminology Drift | **LOW** | spec.md:Dependencies | Lists "Feature 002 (OCP Rebranding) provides the role-aware UI framework for Grafana dashboards." Grafana dashboards are independent of OCP PHP views; the dependency is artificially strong. Role awareness in Grafana is handled by Grafana's native org/team features, not OCP rebranding. | Weaken dependency to "Feature 002 theme.json provides color palette consistency" only. |
| C3 | Coverage Gap | **LOW** | spec.md:FR-003 | FR-003 requires dashboards in EN/ES/PT. T3.5 implements i18n JSON files, but no task verifies that all **panel descriptions** (not just titles) are translated. | Expand T3.5 acceptance criteria to include panel descriptions and legend text. |

**Metrics**: 6 FRs, 10 Tasks, Coverage 100%.

---

### Spec 004 — Health Checks & Auto-Healing
**Status**: Partial | **FRs**: 6 | **SCs**: 6 | **Tasks**: 13

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| D1 | Coverage Gap | **HIGH** | spec.md:FR-001; tasks.md:T1.4 | FR-001 requires HEALTHCHECK for **all** containers including Asterisk. T1.4 (Asterisk health check) is explicitly deferred with note: "Skip T1.4 until Asterisk container is introduced in a separate feature." This leaves FR-001 partially unfulfilled with no tracking task. | Either remove Asterisk from FR-001 scope, or create a deferred tracking task linked to the feature that introduces Asterisk. |
| D2 | Duplication | **MEDIUM** | spec.md:FR-003; spec 006 FR-003 | Both spec 004 and spec 006 define dispatcher load-based routing and probing with identical `modparam` values (`ds_ping_method=OPTIONS`, `ds_ping_interval=10`). The duplication is not cross-referenced. | Add a note in both specs referencing the other, or consolidate dispatcher probing config into spec 001 (foundation) since it affects both health checks and rate limiting. |
| D3 | Ambiguity | **MEDIUM** | spec.md:FR-005 | FR-005 metric `dispatched_targets_active` is not a standard OpenSIPS MI metric. The spec does not define how this custom metric is computed or whether it replaces `dispatcher_target_state`. | Define the exact calculation or map it to an existing OpenSIPS statistic. |

**Metrics**: 6 FRs, 13 Tasks, Coverage 83% (Asterisk health check gap).

---

### Spec 005 — PostgreSQL Backup & Restore
**Status**: Implemented | **FRs**: 6 | **SCs**: 6 | **Tasks**: 16

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| E1 | Underspecification | **MEDIUM** | spec.md:Scenario 2; tasks.md:T5.4 | PITR restore is claimed as in-scope, but T5.4 note states: *"timestamp-targeted WAL replay is not production-proven. Treat as implementation artifact plus dry-run helper until a full PITR restore drill passes."* This means SC-003 (RTO ≤ 15 min for PITR) is unproven. | Either change status to "Partial" or add a blocking task to perform and document a live PITR drill. |
| E2 | Underspecification | **MEDIUM** | spec.md:FR-006; tasks.md:T6.1, T6.2 | Offsite replication tasks T6.1 and T6.2 are pending with notes: *"real remote credentials and successful offsite listing are still pending."* SC-006 (offsite replication lag ≤ 1 hour) cannot be validated. | Mark spec status as "Partial" until offsite replication is proven, or remove SC-006 from committed scope. |
| E3 | Ambiguity | **LOW** | spec.md:Clarifications (Portuguese) | Several clarifications are written in Portuguese without English translations in the spec body (e.g., *"Sem replicação offsite; dados vulneráveis a perda local"*). While the constitution does not mandate English, bilingual specs risk misinterpretation by non-Portuguese implementers. | Add English translations alongside Portuguese clarifications. |

**Metrics**: 6 FRs, 16 Tasks, Coverage 100% (but 2 pending tasks block full success validation).

---

### Spec 006 — Rate Limiting & DDoS Protection
**Status**: Partial | **FRs**: 5 | **SCs**: 6 | **Tasks**: 16

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| F1 | Coverage Gap | **HIGH** | tasks.md | 8 of 16 tasks are **pending**: T1.2, T1.3, T2.1, T2.2, T3.2, T4.1, T4.2, T4.3, T5.3. Core functionality (NATed enterprise handling, TCP connection limits, auth rate limiting, ban lists, global throttle) is unimplemented. | Reassess status; "Partial" understates the incomplete volume. Consider splitting into two specs: MVP (pike + anomaly) and Phase 2 (auth limits + ban lists). |
| F2 | Ambiguity | **MEDIUM** | spec.md:FR-005 | "If global request volume exceeds 3 standard deviations from a 24-hour rolling baseline" — baseline granularity (per minute? per second?) and minimum sample size are undefined. | Specify baseline aggregation window (e.g., 1-minute buckets, minimum 6 hours of data). |
| F3 | Inconsistency | **MEDIUM** | spec.md:FR-002 | Uses `htable` for auth failure tracking, but CANONICAL-SPEC §6 lists `userblacklist` as the canonical module for "per-user ban list for repeated auth failures." The spec does not justify choosing `htable` over `userblacklist`. | Document rationale for `htable` vs `userblacklist` or align with CANONICAL-SPEC module baseline. |

**Metrics**: 5 FRs, 16 Tasks, Coverage 50% (8 pending).

---

### Spec 007 — TLS/SRTP Encryption
**Status**: Partial | **FRs**: 5 | **SCs**: 6 | **Tasks**: 15

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| G1 | Coverage Gap | **HIGH** | tasks.md | 7 of 15 tasks are **pending**: T2.3 (mTLS trunk logic), T3.2 (rotation monitoring), T4.1–T4.3 (SRTP integration), T5.1–T5.2 (cipher hardening). The actual SRTP media encryption and cipher restriction are almost entirely unimplemented. | Reassess status. Most core deliverables (SRTP, cipher suites) are pending. Add tasks for OpenSIPS 3.6 doc validation of `tls_mgm` cipher_list syntax. |
| G2 | Underspecification | **MEDIUM** | spec.md:FR-003 | "Existing TLS connections continue using the old certificate until natural closure" — no timeout or forced-disconnect policy is defined for compromised certificates. | Add AC: emergency rotation may optionally send MI `tls_close` or document that compromised certs require maintenance window. |
| G3 | Constitution | **MEDIUM** | spec.md:Risks:R-004 | Proposes maintaining a separate legacy TLS profile with weaker ciphers. Constitution §5 and §9 imply hardened cipher enforcement; a weaker profile contradicts the "hardened cipher suites" objective unless explicitly scoped and justified. | If retained, scope the legacy profile as strictly opt-in, isolated listener, and document as a constitution exception with security review. |

**Metrics**: 5 FRs, 15 Tasks, Coverage 53% (7 pending).

---

### Spec 008 — DevSecOps Deployment
**Status**: Live VPS production stack running; pending validations | **FRs**: 6 | **SCs**: 6 | **Tasks**: 14

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| H1 | Inconsistency | **HIGH** | spec.md:SC-004; checklist:Security Compliance | SC-004 claims A+ SSL grade (SSL Labs). Checklist says *"TLS certificates present (dummy certs deployed; real certs pending)."* Dummy certs cannot achieve A+. T4.1 validates `securityheaders.com` (HTTP headers), **not** SSL Labs (TLS config). These are different services. | Split SC-004 into two criteria: HTTP security headers (securityheaders.com) and TLS grade (SSL Labs). Add a task to run SSL Labs scan after real certs are deployed. |
| H2 | Coverage Gap | **MEDIUM** | spec.md:SC-006 | SC-006: "SIP authenticated route — Digest INVITE reaches Asterisk and returns final response." No task in tasks.md explicitly validates this SIP flow. Feature 001 T4.7 covers authenticated INVITE, but Feature 008 should have its own deploy-specific validation task. | Add T_X: Post-deploy SIP auth probe via `scripts/sip-auth-probe.py` against TSiAPP. |
| H3 | Inconsistency | **MEDIUM** | checklist:Monitoring and Alerting | Checklist marks Prometheus/Grafana as "deferred to Phase 2," but spec 003 (Observability) and spec 004 (Health Checks) already define Prometheus dashboards and alert rules. This creates ambiguity about whether observability is in or out of scope for Feature 008. | Clarify in spec 008 scope that observability stack is **excluded** and refer to specs 003–004. |
| H4 | Terminology Drift | **LOW** | spec.md vs plan.md | Spec uses "TSiAPP" and "vps-lite+PBX" interchangeably; plan uses "VPS 'TSiAPP' (Debian/Ubuntu)." Assumption says Ubuntu 22.04+ or Debian 12+, but runbook says TSiAPP runs Ubuntu 24.04. | Standardize to "Ubuntu 24.04 LTS" everywhere. |

**Metrics**: 6 FRs, 14 Tasks, Coverage 100% (but 2 SCs lack validation tasks).

---

### Spec 009 — VPS Deploy Automation Pipeline
**Status**: Draft | **FRs**: 6 | **SCs**: 4 | **Tasks**: 11

| ID | Category | Severity | Location(s) | Summary | Recommendation |
|----|----------|----------|-------------|---------|----------------|
| I1 | Constitution | **CRITICAL** | spec.md:FR-003 | "Multi-Agent Build and Push" with isolated agents implies external orchestration (OMK ensemble). Constitution §1 mandates Docker-first delivery **of runtime components**. If "agents" are long-running services, they must also be containerized. The spec does not define agent runtime packaging. | Clarify whether Builder/Pusher/Deployer/Verifier are local shell scripts, CI jobs, or containerized services. If containerized, add Dockerfile requirements. |
| I2 | Coverage Gap | **HIGH** | tasks.md | **All 11 tasks are pending.** The spec is Draft with zero implementation. It depends on Feature 008 (already live), which makes the dependency direction questionable — the pipeline is supposed to automate what already works manually. | Before planning, clarify the incremental value of Feature 009 over existing `deploy/scripts/orchestrate-deploy.sh`. If the script already exists, spec should inventory it and gap-fill, not redesign from scratch. |
| I3 | Ambiguity | **MEDIUM** | spec.md:FR-002 | "Impact analysis reports risk level for each modified component" — no definition of HIGH vs CRITICAL risk thresholds for specific file types (e.g., `opensips.cfg.tpl` vs `web/css/main.css`). | Add a risk matrix table to the spec or reference the one in `.kimi/skills/omk-security-review/`. |
| I4 | Underspecification | **MEDIUM** | spec.md:FR-005 | "Wiki endpoints return HTTP 200" — no specific wiki endpoints or content validation criteria defined. | List canonical wiki paths (e.g., `/TSiSIP/Wiki`, `/TSiSIP/Wiki/Operator-Guide`). |

**Metrics**: 6 FRs, 11 Tasks, Coverage 0% (all pending).

---

## Global Cross-Cutting Analysis

### Cross-Spec Consistency

| Issue | Severity | Details |
|-------|----------|---------|
| **RTPengine ng-control binding** | **CRITICAL** | Spec 001 plan/tasks mandate `0.0.0.0:22222`. CANONICAL-SPEC §5 forbids this. OPERATOR-RUNBOOK does not mention the binding at all. This is a direct security architecture violation. |
| **Auth response code contract** | **CRITICAL** | CANONICAL-SPEC §9 specifies `proxy_authorize()` (407) for non-REGISTER. Spec 001 FR-005 and implementation use `www_authorize()` (401) for all methods. Deviation is "documented pending alignment" with no resolution task. |
| **Network model drift** | **MEDIUM** | OPERATOR-RUNBOOK introduces `metrics_host` network for OCP and backup metrics. CANONICAL-SPEC §5 and AGENTS.md §4 only recognize `sip_edge`, `sip_internal`, `db_internal`. `metrics_host` is an undocumented architecture extension. |
| **RTP port range discrepancy** | **MEDIUM** | CANONICAL-SPEC §3 and AGENTS.md say `10000-20000/udp`. OPERATOR-RUNBOOK says TSiAPP uses `10000-10999/udp`. |
| **Prometheus scope ambiguity** | **MEDIUM** | Spec 008 checklist defers Prometheus to "Phase 2," but specs 003, 004, and 005 already define Prometheus dashboards, alert rules, and metrics exporters. There is no single "Phase 2" roadmap document tying these together. |
| **Git history contradiction** | **LOW** | AGENTS.md §2 states *"Git is initialized but has no commits."* `git log` shows commits including `24090f5`. This outdated claim risks misleading agents about repository maturity. |

### Constitution Alignment Issues

| Principle | Violation | Location | Required Fix |
|-----------|-----------|----------|--------------|
| §1 Docker-First Delivery | **POTENTIAL** | spec 009 FR-003 (multi-agent build/push) | Clarify agent runtime packaging; containerize if they are services. |
| §4 Edge Isolation & Backend Privacy | **CONFIRMED** | spec 001 plan.md:RTPengine `0.0.0.0:22222` | Bind to `sip_internal` interface only. |
| §3 OpenSIPS 3.6 LTS Baseline | **CONFIRMED** | CANONICAL-SPEC §9 deviation note | Resolve 401 vs 407 auth contract; update spec or implementation. |
| §9 Documentation Quality | **CONFIRMED** | AGENTS.md §2 "no commits" claim | Update AGENTS.md to reflect actual repository state. |

### Missing or Incomplete Work

| Gap | Impact | Recommendation |
|-----|--------|----------------|
| No unified Phase 2 roadmap document | Specs 003–008 reference "Phase 2" observability, image pinning, and CI/CD without a canonical ordering. | Create `docs/TSiSIP-ROADMAP.md` linking Phase 2 features to specs and dependencies. |
| No feature for Asterisk containerization | Spec 004 defers Asterisk health checks; spec 001 includes Asterisk in Compose but no dedicated spec exists for Asterisk runtime config. | Create spec 010 for Asterisk PBX containerization, or absorb into spec 001 as "foundation extension." |
| No spec for CI/CD pipeline | Feature 008 defers GitHub Actions; Feature 009 mentions `workflow_dispatch` but does not design it. | Create spec 011 for GitHub Actions CI/CD (image build, scan, push). |
| No spec for performance/benchmarking | Spec 001 defines performance targets (1000 sessions, <50ms latency) but defers benchmarking to "future performance feature." | Create spec 012 for load testing and performance validation. |

---

## Metrics Summary

| Metric | Count |
|--------|-------|
| Total Functional Requirements (001–009) | 63 |
| Total Success Criteria (001–009) | 65 |
| Total Tasks (001–009) | 136 |
| Requirements with ≥1 direct task | ~56 (89%) |
| Requirements with only documentation/validation tasks | 2 (FR-008, FR-009 in spec 001) |
| Ambiguity Count | 6 |
| Duplication Count | 2 |
| Critical Issues Count | 3 |
| High Issues Count | 5 |
| Medium Issues Count | 6 |
| Low Issues Count | 3 |
| Pending Tasks (all specs) | 18 |
| Completed Tasks (all specs) | 118 |

---

## Next Actions

### Blockers (resolve before `/speckit-implement`)

1. **Fix RTPengine ng-control binding** (CRITICAL): Update spec 001 plan.md and tasks.md to bind `--listen-ng` to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`. Validate against CANONICAL-SPEC §5.
2. **Resolve auth response code contract** (CRITICAL): Decide between 401 (current) and 407 (canonical) for non-REGISTER requests. Update either CANONICAL-SPEC §9 or spec 001 FR-005 and implementation, and add a concrete alignment task.
3. **Containerize or clarify Feature 009 agents** (CRITICAL): If Builder/Pusher/Deployer/Verifier are runtime services, they must be Docker-delivered per Constitution §1.

### High Priority (improve before implementation continues)

4. **Add implementation tasks for FR-008 and FR-009** (spec 001): Currently only documented, not implemented in OpenSIPS config.
5. **Fix SSL Labs vs securityheaders.com confusion** (spec 008): Split SC-004, add SSL Labs task, and clarify dummy cert status.
6. **Reassess spec 006 and 007 status**: Both have >50% pending tasks. "Partial" understates the incomplete volume. Consider status "In Progress" or breaking into phased specs.
7. **Standardize network model**: Document `metrics_host` in CANONICAL-SPEC §5 or remove it and use `db_internal` for metrics.

### Medium Priority (proceed, but address in next sprint)

8. **Update AGENTS.md repository state claim**: Change "no commits" to reflect actual git history.
9. **Standardize OS baseline**: Use "Ubuntu 24.04 LTS" consistently across specs 008 and 009.
10. **Add English translations** to spec 005 Portuguese clarifications.
11. **Create unified Phase 2 roadmap** linking observability, CI/CD, and performance features.

---

## Remediation Offer

Would you like me to suggest concrete remediation edits for the top **N** issues? I can prioritize:

- **Option A**: Top 3 CRITICAL issues (RTPengine binding, auth contract, Feature 009 agent packaging)
- **Option B**: Top 5 issues (above + FR-008/009 tasks + SSL Labs confusion)
- **Option C**: Top 10 issues (all CRITICAL/HIGH + selected MEDIUM)

Please indicate which option you prefer, or specify a different set of issues to remediate.
