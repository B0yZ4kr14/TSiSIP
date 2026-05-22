# Memory Synthesis

## Current Scope
- Feature: 019-spec-kit-memory-hub-integration
- Spec: Feature 019: Spec Kit Memory Hub Integration
- Feature folder: specs/019-spec-kit-memory-hub-integration
- Spec context: # Feature 019 : Spec Kit Memory Hub Integration ## Overview The TSiSIP project currently operates Speckit governance without the spec-kit-memory-hub extension . This extension provides durable , queryable memory for architectural...

## Relevant Project Context
- [none]

## Relevant Decisions
- [D1] Run speckit.memory-md.capture after completing significant work. Review the proposed changes. Approve or edit before committing. (Source: `docs/memory/INDEX.md`)
- [D2] Date : 2026-05-19 Context : Need web-based admin for subscribers and dispatcher Decision : Build PHP OCP pages with PDO prepared statements, CSRF tokens, role checks Consequences : Constitution V1 requires amendment for intentional subscriber writes Status : Active (L3 Decision) — see CUP-012-01 (Source: `docs/memory/DECISIONS.md`)
- [D3] Architecture decisions are documented in docs/TSiSIP-CANONICAL-SPEC.md. Agent orchestration rules live in AGENTS.md and .specify/memory/agent-governance.md. All specs live in specs/{NNN-feature-name}/ with spec.md, plan.md, tasks.md. (Source: `.specify/memory/constitution.md`)
- [D4] Governance changes require explicit approval from the solution-architecture agent. Architecture enforcement changes target .specify/memory/architecture_constitution.md. Repeated drift triggers an Architecture Constitution Update Proposal via /speckit.architecture-guard.init. (Source: `.specify/memory/constitution.md`)
- [D5] Date : 2026-05-16 Context : SIP Digest requires HA1; computing on-the-fly is slower Decision : Store HA1, HA1_SHA256, HA1_SHA512T256; set calculate_ha1=0 Consequences : Password never stored; salt rotation requires re-hash Status : Immutable (L1 Constitution) (Source: `docs/memory/DECISIONS.md`)

## Active Architecture Constraints
- [none]

## Accepted Deviations
- [none]

## Relevant Security Constraints
- [S1] Agent : Kimi (omk-project harness ) Tasks Completed : Installed memory-md extension (v0.8.5) via specify extension add memory-md Verified CLI compatibility and extension registration Created security assessment (SEC-019-EVI-001) Created agent memory governance (SEC-019-EVI-002) Updated security evidence index with Feature 019 artefacts Created memory-hub config .yml with TSiSIP-specific indexing rules Created docs /memory/ directory with INDEX , PROJECT_CONTEXT , ARCHITECTURE , DECISIONS , BUGS , WORKLOG Added .spec-kit-memory/ and lock files to .gitignore Decisions Made : MSL Applicability : Non-MSL (TSiSIP-SEC-019-MSL-EXEMPT-001)... (Source: `docs/memory/WORKLOG.md`)
- [S2] P0 findings (security, auth contract, topology leaks) block release until resolved. Non-blocking architecture drift becomes tracked refactor work in .specify/memory/. Changes to docs/TSiSIP-CANONICAL-SPEC.md require multi-agent validation per docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md. (Source: `.specify/memory/constitution.md`)
- [S3] Docker-image-first : All runtime components must be delivered as project-owned Docker images. Bare-metal or VM-first installations are rejected. PostgreSQL-only : OpenSIPS auth, routing, and tenant metadata use PostgreSQL exclusively. (Source: `.specify/memory/constitution.md`)

## Related Historical Lessons
- [B1] Date : 2026-05-19 Severity : MEDIUM Symptom : External domain resolution fails inside containers (SERVFAIL for 127.0.0.11) Root Cause : systemd-resolved binds port 53 on host, conflicting with Docker's embedded DNS Fix : Use --network host for certbot; permanent fix pending Prevention : Document in deployment runbook; verify DNS before cert renewal (Source: `docs/memory/BUGS.md`)

## Conflict Warnings
- [none]

## Retrieval Notes
- Index entries considered: 10
- Source sections read: 10
- Budget status: within limit
