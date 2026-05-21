# Feature 019: Spec Kit Memory Hub Integration

## Overview

The TSiSIP project currently operates Speckit governance without the spec-kit-memory-hub extension. This extension provides durable, queryable memory for architectural decisions, constraints, and lessons learned.

This feature installs and configures spec-kit-memory-hub as a first-class Speckit extension, integrates it with the existing .specify/memory/ directory, and establishes governance rules for what may be captured in agent-accessible memory.

## Security Governance Preset

### Memory-Safe Language Assessment

| Language | Memory-Safe | Justification |
|---|---|---|
| PHP 8.2 | No | Managed runtime with garbage collection, but buffer overflows and use-after-free remain possible in extensions. Mitigated via Docker image pinning and Trivy scanning. |
| C (OpenSIPS, RTPengine, Asterisk) | No | Manual memory management. OpenSIPS 3.6 LTS is the only C component exposed to the public internet; mature project with regular security audits. |
| Shell / JavaScript (build) | N/A | Build-time only; not runtime-exposed. |
| SQL (PostgreSQL) | N/A | Declarative; managed by PostgreSQL 16. |

### Framework Relevance

| Framework | Relevance | Status |
|---|---|---|
| NIST SSDF | Relevant | docs/security/008-nist-ssdf-gap-analysis.md exists |
| CWE Top 25 | Relevant | docs/security/008-cwe-top-25-mapping.md exists |
| OWASP ASVS | Relevant | docs/security/008-owasp-asvs-gap-analysis.md exists |
| SBOM | Relevant | docs/security/008-supply-chain-status.md exists; P1 for CI |
| VEX | Relevant | Tracked in 008-supply-chain-status.md; P1 for CI |
| SLSA | Relevant | Tracked in 008-supply-chain-status.md; P1 for CI |

### Security Evidence Artefacts

- Create: docs/security/019-memory-hub-security-assessment.md
- Create: docs/security/019-agent-memory-governance.md
- Update: docs/security/008-security-evidence-index.md

## Motivation

Without memory-hub:
- Architecture decisions are buried in constitution.md without structured querying.
- Security drift lessons are not proactively surfaced to future specs.
- Cross-feature traceability requires manual grepping across 18+ spec directories.

With memory-hub:
- speckit.memory-md.prepare-context synthesizes relevant historical decisions before planning.
- speckit.memory-md.capture persists new decisions with governance approval.

## Functional Goals

1. Install Memory Hub Extension
2. Configure Optimizer
3. Bootstrap Memory Corpus
4. Establish Capture Governance
5. Integration Validation

## Non-Goals

- Replacing .specify/memory/*.md with a database-only model.
- Real-time memory sync across multiple agent sessions.
- Natural-language query interface beyond Speckit CLI commands.

## Security Requirements

| ID | Requirement |
|---|---|
| R1 | Memory-hub must never store secrets, credentials, private keys, or runtime env values |
| R2 | Memory capture requires explicit approval via governance workflow |
| R3 | Memory entries must include source attribution |
| R4 | PII and CDR contents must be pseudonymized or excluded |
| R5 | Memory index must be gitignore protected |
| R6 | Access to memory synthesis output respects role hierarchy (devops+) |

## Acceptance Criteria

- [ ] AC1: spec-kit-memory-hub appears in .specify/extensions.yml installed list
- [ ] AC2: .specify/extensions/memory-md/config.yml exists with optimizer.enabled: true
- [ ] AC3: speckit.memory-md.prepare-context runs without error
- [ ] AC4: Existing .specify/memory/*.md files are indexed and queryable
- [ ] AC5: docs/security/019-memory-hub-security-assessment.md exists and is approved
- [ ] AC6: docs/security/019-agent-memory-governance.md exists and is approved
- [ ] AC7: docs/security/008-security-evidence-index.md updated with Feature 019 entries
- [ ] AC8: A test capture successfully persists a non-sensitive decision and awaits approval

## Architecture Decisions

### AD-1: Markdown-First Memory Model
Memory-hub indexes existing .md files; it does not replace them.

### AD-2: Project-Local Index
The memory index lives inside .specify/extensions/memory-md/ and is gitignore protected.

### AD-3: Explicit Approval Gate
All memory captures require human or governance-agent approval.

## References

- .specify/extensions.yml
- .specify/memory/drift-lessons-2026-05-21.md
- docs/security/008-supply-chain-status.md
