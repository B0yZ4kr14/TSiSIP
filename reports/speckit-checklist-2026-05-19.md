# Project-Wide Requirements Quality Checklist: TSiSIP

**Purpose**: Validate the quality, clarity, completeness, and consistency of requirements across active specs (013, 018, 021, 024) and cross-cutting findings from brownfield-scan, version-guard, and memorylint.
**Created**: 2026-05-19
**Project**: TSiSIP — SIP Edge Proxy Platform
**Scope**: Active specs, infrastructure governance, supply-chain determinism, resource governance

---

## Requirement Completeness

- [ ] CHK001 — Are acceptance criteria defined for every ongoing/partial spec (013, 018, 021, 024) with explicit completion gates? [Completeness, Spec 013, 018, 021, 024]
- [ ] CHK002 — Are infrastructure healthcheck requirements specified for *all* services including certbot and tailscale-cert restart-loop scenarios? [Gap, Spec 022, 024]
- [ ] CHK003 — Are documentation completeness requirements (CONTRIBUTING.md, .editorconfig, ADR directory) explicitly scoped in any spec, or are they intentionally out of scope? [Gap, Brownfield 5.4]
- [ ] CHK004 — Are container resource limit requirements specified for every service in the vps-lite profile, including certbot, tailscale-cert, and certbot-exporter? [Gap, MemoryLint F1]
- [ ] CHK005 — Are supply-chain determinism requirements (image pinning, digest pinning, lockfiles) defined for dev, prod, and VPS environments equally? [Completeness, Version Guard V1–V3]
- [ ] CHK006 — Are PHP memory tuning and opcache requirements documented in any spec or plan? [Gap, MemoryLint F2]
- [ ] CHK007 — Are backup compression resource bounds (memory, CPU) specified in backup/restore requirements? [Gap, MemoryLint F3]
- [ ] CHK008 — Are OpenSIPS per-process memory (pkg_mem_size) calculation requirements documented, given shm_mem_size is specified? [Gap, MemoryLint F4]
- [ ] CHK009 — Are post-remediation verification requirements (e.g., "run brownfield scan after fix") defined as acceptance criteria in Spec 024? [Completeness, Spec 024]
- [ ] CHK010 — Are multi-spec consistency requirements (e.g., env var naming, network naming conventions) defined in a canonical governance spec? [Gap, AGENTS.md 7]

## Requirement Clarity

- [ ] CHK011 — Is "ongoing / partial" status for specs 013, 018, 021, 024 quantified with specific remaining tasks or completion percentages? [Clarity, Sync Report 3.1]
- [ ] CHK012 — Are the acceptance criteria for Spec 024 (Brownfield Remediation) unambiguous about which brownfield findings (B1–B12) map to which AC? [Clarity, Spec 024]
- [ ] CHK013 — Is the term "healthy" quantified for services like certbot and tailscale-cert that exhibit restart loops under certain conditions? [Clarity, Sync Report 4.1]
- [ ] CHK014 — Are Python dependency pinning requirements clear about whether requirements.txt must use exact pins, hashed pins, or lockfiles? [Clarity, Version Guard V3]
- [ ] CHK015 — Is "dynamic IP discovery" in deploy scripts (Spec 024 AC4) defined with the specific command or mechanism to be used? [Clarity, Spec 024]
- [ ] CHK016 — Are FR-ID duplicate resolution rules in Spec 018 explicitly defined with assignment logic and conflict resolution? [Clarity, Spec 018, fr-id-duplicates.json]
- [ ] CHK017 — Is the memory reservation formula (e.g., "50% of limit") explicitly specified for services lacking reservations? [Clarity, MemoryLint F1]
- [ ] CHK018 — Are "real TLS certificates" and "rclone/MinIO offsite replication" requirements in Feature 005 defined with environment preconditions and credential boundaries? [Clarity, Consolidated Report 4]

## Requirement Consistency

- [ ] CHK019 — Are image tag requirements consistent across docker-compose.yml, docker-compose.prod.yml, and docker-compose.vps.yml (e.g., mandatory vs fallback)? [Consistency, Version Guard V1–V2]
- [ ] CHK020 — Are network naming conventions (sip_edge, sip_internal, db_internal) consistently enforced across all specs and AGENTS.md? [Consistency, AGENTS.md 4]
- [ ] CHK021 — Are healthcheck interval/timeout/retry values consistent across Dockerfiles, compose files, and spec acceptance criteria? [Consistency, Spec 022 AC1]
- [ ] CHK022 — Are Python service base image versions consistent (single minor version recommended) across anomaly-detector, opensips-exporter, and admin-api? [Consistency, Version Guard V3]
- [ ] CHK023 — Are the "Out of Scope" sections in Spec 022 and Spec 024 mutually exclusive with their "Acceptance Criteria" sections? [Consistency, Spec 022, 024]
- [ ] CHK024 — Are security requirements (R1–R3) in Spec 024 consistent with the security constraints defined in the architecture constitution? [Consistency, Spec 024]

## Acceptance Criteria Quality

- [ ] CHK025 — Can AC10 in Spec 024 ("Post-fix brownfield scan shows zero HIGH/MEDIUM findings") be objectively measured with a defined scanner version and baseline? [Measurability, Spec 024]
- [ ] CHK026 — Are load-test thresholds (e.g., "100 concurrent REGISTER requests" in Spec 022 Out of Scope) defined as measurable acceptance criteria elsewhere? [Measurability, Spec 022]
- [ ] CHK027 — Is the "second operator can execute runbook without asking clarifying questions" criterion (Spec 022 AC5) measurable with a time bound (15 minutes) and validation method? [Measurability, Spec 022]
- [ ] CHK028 — Are memory limit values in docker-compose.vps.yml traceable to a requirement or calculation in a spec? [Traceability, MemoryLint Capacity Planning]
- [ ] CHK029 — Is the "zero HIGH/MEDIUM findings" criterion in Spec 024 AC10 pinned to a specific brownfield scan report date/commit? [Measurability, Spec 024]
- [ ] CHK030 — Are the "healthy" criteria for the unified nginx proxy and TSiAPP endpoints quantified with expected HTTP status codes and response-time thresholds? [Measurability, Sync Report 4.4]

## Scenario Coverage

- [ ] CHK031 — Are alternate-flow requirements defined for Spec 024 when docker network inspect fails or returns multiple subnets? [Coverage, Spec 024]
- [ ] CHK032 — Are error-handling requirements defined for Spec 018 when FR-ID duplicate remediation encounters unresolvable collisions? [Coverage, Spec 018]
- [ ] CHK033 — Are rollback requirements defined for brownfield remediation changes (e.g., reverting SHA-pinned images if they break)? [Coverage, Spec 024]
- [ ] CHK034 — Are degradation requirements defined for when RTPengine kernel table creation fails (userspace fallback memory impact)? [Coverage, MemoryLint M8]
- [ ] CHK035 — Are concurrent-query scenarios addressed for PostgreSQL work_mem stack risk (200 connections x 16MB)? [Coverage, MemoryLint F5]
- [ ] CHK036 — Are DNS-resolution-failure scenarios (certbot restart loops) addressed in deployment requirements? [Coverage, Sync Report 4.1]

## Edge Case Coverage

- [ ] CHK037 — Are edge cases defined for Spec 024 when .env.example variables are commented out vs. empty-string vs. placeholder? [Edge Case, Spec 024 AC5]
- [ ] CHK038 — Are edge cases defined for OpenSIPS memory when shm_mem_size + (child_processes x pkg_mem_size) exceeds container limit? [Edge Case, MemoryLint F4]
- [ ] CHK039 — Are edge cases defined for backup container behavior when gzip compression OOMs mid-dump? [Edge Case, MemoryLint F3]
- [ ] CHK040 — Are edge cases defined for PHP OCP when subscriber exports exceed memory_limit? [Edge Case, MemoryLint F2]
- [ ] CHK041 — Are edge cases defined for version-guard validation when a new upstream release introduces a CVE in a pinned digest? [Edge Case, Version Guard V2]
- [ ] CHK042 — Are edge cases defined for the unified nginx proxy when Cloudflare Origin CA certificate expires (2041)? [Edge Case, Sync Report G6]

## Non-Functional Requirements

- [ ] CHK043 — Are performance requirements for the vps-lite stack (10 services, ~7.5GB RAM) quantified under expected SIP load? [NFR, Spec 022]
- [ ] CHK044 — Are observability requirements defined for the vps-lite profile when Prometheus/Grafana are disabled? [NFR, Spec 022, STATUS.md]
- [ ] CHK045 — Are security hardening requirements (cap_drop, cap_add, no-new-privileges) specified as mandatory for *all* new services? [NFR, AGENTS.md 9]
- [ ] CHK046 — Are certificate rotation requirements defined with alert thresholds for the 15-year Cloudflare Origin CA cert? [NFR, Sync Report G6]
- [ ] CHK047 — Are resource governance requirements (memory limits, reservations, swap limits) defined as project-wide NFRs rather than per-feature ad-hoc additions? [NFR, MemoryLint Executive Summary]
- [ ] CHK048 — Are CI/CD determinism requirements (pinned GitHub Actions, reproducible builds) defined as project-wide NFRs? [NFR, Version Guard V5]

## Dependencies & Assumptions

- [ ] CHK049 — Is the assumption that Docker userland-proxy=false is a permanent host configuration documented and validated? [Assumption, Spec 022, Sync Report]
- [ ] CHK050 — Is the dependency on Cloudflare for TLS termination explicitly documented with fallback requirements? [Dependency, Sync Report 4.3]
- [ ] CHK051 — Is the dependency on upstream provider/NAT/Tailscale ACL for SIP port exposure documented with an owner and SLA? [Dependency, STATUS.md Active Issues]
- [ ] CHK052 — Is the assumption that docker network inspect is available and executable on the VPS host validated in Spec 024 requirements? [Assumption, Spec 024]
- [ ] CHK053 — Is the dependency on specific Python minor versions (3.11 vs 3.12) documented with justification in specs or AGENTS.md? [Dependency, Version Guard V3]
- [ ] CHK054 — Is the assumption that host memory pressure (~97% utilization) will not affect TSiSIP container stability documented and risk-assessed? [Assumption, Consolidated Report 2]

## Ambiguities & Conflicts

- [ ] CHK055 — Is the scope boundary between Spec 021 (Brownfield Security Production Hardening) and Spec 024 (Brownfield Remediation) clearly defined to prevent overlap or gaps? [Conflict, Spec 021, 024]
- [ ] CHK056 — Is the conflict between "certbot restart loop" (active issue in STATUS.md) and "certbot healthy" (infrastructure state table) resolved in requirements? [Conflict, STATUS.md Active Issues, Sync Report 4.1]
- [ ] CHK057 — Is the ambiguity around .env.example placeholder values vs. production values (tsiapp.io) resolved with explicit rules? [Ambiguity, Sync Report G5]
- [ ] CHK058 — Is the scope of "15 specs lack blueprints" (Sync Report G4) assigned to a specific spec or governance artifact? [Ambiguity, Sync Report G4]
- [ ] CHK059 — Is the ambiguity in Spec 013 status ("Ongoing / Partial" vs. "Complete" in other sources) resolved with clear definition of done? [Ambiguity, Sync Report 3.1, STATUS.md]
- [ ] CHK060 — Is the conflict between "RTPengine kernel module deferred" and "userspace fallback acceptable" resolved with explicit acceptance criteria for either path? [Conflict, MemoryLint M8, STATUS.md]

## Traceability & Governance

- [ ] CHK061 — Is a requirement and acceptance criteria ID scheme (REQ-NNN, AC-NNN) established for all active specs to enable cross-artifact traceability? [Traceability, Spec 022, 023, 024]
- [ ] CHK062 — Are architecture decisions (AD-022-1, AD-022-2, AD-022-3, AD-024-1, AD-024-2, AD-024-3) traceable to specific acceptance criteria or plan tasks? [Traceability, Spec 022, 024]
- [ ] CHK063 — Are brownfield findings (B1–B12) traceable to specific spec ACs, plan tasks, or governance artifacts? [Traceability, Spec 024]
- [ ] CHK064 — Are memorylint findings (M1–M10, F1–F5) traceable to spec requirements or architecture decisions? [Traceability, MemoryLint Findings]
- [ ] CHK065 — Are version-guard findings (V1–V5) traceable to CI gates or pre-deploy checklists? [Traceability, Version Guard Findings]
- [ ] CHK066 — Is the consolidated quality gate report (2026-05-19) referenced as a baseline in all active specs requiring remediation? [Traceability, Consolidated Report]

---

## Summary

| Dimension | Items | Coverage Focus |
|-----------|-------|----------------|
| Requirement Completeness | CHK001–CHK010 | Active specs, infrastructure gaps, resource governance |
| Requirement Clarity | CHK011–CHK018 | Quantification, mechanism definition, scope boundaries |
| Requirement Consistency | CHK019–CHK024 | Cross-file alignment, convention enforcement |
| Acceptance Criteria Quality | CHK025–CHK030 | Measurability, traceability, baselines |
| Scenario Coverage | CHK031–CHK036 | Alternate flows, degradation, concurrency |
| Edge Case Coverage | CHK037–CHK042 | OOM, expiry, fallback, overflow |
| Non-Functional Requirements | CHK043–CHK048 | Performance, security, observability, CI determinism |
| Dependencies & Assumptions | CHK049–CHK054 | Host config, upstream ACL, version drift, memory pressure |
| Ambiguities & Conflicts | CHK055–CHK060 | Spec overlap, status ambiguity, scope conflicts |
| Traceability & Governance | CHK061–CHK066 | ID schemes, decision-to-AC linkage, scan-to-spec mapping |

**Total Items**: 66  
**Depth**: Standard (project-wide, reviewer-oriented)  
**Audience**: Spec authors, architecture reviewers, QA gatekeepers  
**Timing**: Post-stabilization (Feature 022/023/024 complete), pre-next-phase planning  
**Explicit Must-Haves Incorporated**: Brownfield-scan findings (certbot health, .editorconfig, CONTRIBUTING.md), version-guard findings (image pinning, Python lockfiles), memorylint findings (reservations, PHP tuning, backup bounds)
