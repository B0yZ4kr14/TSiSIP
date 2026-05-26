# Feature 019: Spec Kit Memory Hub Integration

**Status**: Completed

## Overview

The TSiSIP project currently operates Speckit governance without the spec-kit-memory-hub extension. This extension provides durable, queryable memory for architectural decisions, constraints, and lessons learned.

This feature installs and configures the `memory-md` Spec Kit extension (also referred to as the memory hub) as a first-class Speckit extension, integrates it with the existing `.specify/memory/` directory, and establishes governance rules for what may be captured in agent-accessible memory.

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

- [x] AC1: spec-kit-memory-hub appears in .specify/extensions.yml installed list
- [x] AC2: `.specify/extensions/memory-md/config.yml` exists with `optimizer.enabled: false` (local-only mode — no remote embedding API or API keys required)
- [x] AC3: speckit.memory-md.prepare-context runs without error
- [x] AC4: Existing .specify/memory/*.md files are indexed and queryable
- [x] AC5: docs/security/019-memory-hub-security-assessment.md exists and is approved
- [x] AC6: docs/security/019-agent-memory-governance.md exists and is approved
- [x] AC7: docs/security/008-security-evidence-index.md updated with Feature 019 entries
- [x] AC8: A test capture successfully persists a non-sensitive decision; the approval gate requires explicit human confirmation before the entry is committed to `docs/memory/`

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

## User Scenarios & Testing

### Scenario 1: Primary happy-path flow
- **Given** the feature is enabled and all dependencies are healthy
- **When** an authorized user performs the canonical action
- **Then** the system responds correctly and produces the expected outcome

### Scenario 2: Error or edge-case handling
- **Given** the feature is enabled
- **When** an invalid input or failure condition occurs
- **Then** the system fails gracefully with a clear error and no data corruption

### Scenario 3: Administrative or operational flow
- **Given** an operator with appropriate role permissions
- **When** the operator inspects or modifies configuration
- **Then** the change is persisted, auditable, and reflected in runtime behavior


## Requirements

### Functional Requirements

#### FR-019-001: Core Capability
**Description**: The system shall provide the primary capability described in this feature specification.
**Acceptance Criteria**:
- The capability is available when the feature is enabled.
- The capability integrates with existing TSiSIP components (OpenSIPS, PostgreSQL, OCP) without regression.

#### FR-019-002: Configuration & Persistence
**Description**: All configuration changes shall be persisted to PostgreSQL and reflected in runtime behavior without requiring a full stack restart.
**Acceptance Criteria**:
- Configuration changes survive container restarts.
- Invalid configuration is rejected at the validation gate.

#### FR-019-003: Observability & Audit
**Description**: The feature shall emit metrics or audit events compatible with the TSiSIP Prometheus/Grafana and OCP audit logging pipelines.
**Acceptance Criteria**:
- Metrics or audit events are visible in the appropriate dashboard or log.
- Failure conditions are logged with sufficient context for debugging.


## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-019-001 | Feature functional completeness | End-to-end validation test pass rate | 100% |
| SC-019-002 | Configuration persistence | Restart test with prior configuration | Pass |
| SC-019-003 | Zero regression in existing flows | Existing integration tests pass rate | 100% |
| SC-019-004 | Observability coverage | Metrics/audit events present | 100% of mutating actions |

