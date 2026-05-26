# Feature 013: Brownfield Residual Findings Remediation

**Status**: Completed

## Overview

Post-remediation validation scan identified 3 new findings after the 5-cycle brownfield remediation. This feature addresses all residual and new findings to achieve zero outstanding brownfield items.

## Goals

1. **B14 — Fix backup script residual bug**: Remove orphaned ALLOW_UNENCRYPTED_BACKUPS reference in docker/backup/backup.sh line 31.
2. **B15 — Add missing healthchecks**: Add healthcheck blocks to anomaly-detector and backup services across all compose files.
3. **B16 — Document CI latest tag**: Add comment in CI workflow explaining that :latest is CI artifact only, not for production.

## Non-Goals

- Full CI/CD pipeline redesign.
- Changes to anomaly-detector or backup application logic.
- New feature development.

## Acceptance Criteria

- [x] AC1: docker/backup/backup.sh runs without unbound variable error when encryption key is present.
- [x] AC2: docker/backup/backup.sh fails fatally when encryption key is missing.
- [x] AC3: docker-compose.yml has healthcheck on anomaly-detector and backup.
- [x] AC4: docker-compose.prod.yml has healthcheck on anomaly-detector and backup.
- [x] AC5: docker-compose.vps.yml has healthcheck on backup.
- [x] AC6: CI scan (scripts/ci-scan.sh) passes after changes.
- [x] AC7: All changes committed with conventional commits.

## References

- reports/brownfield-scan-2026-05-20-post-remediation.md
- evidence/remediation/ciclo-3/b8-backup-encryption-fix.md

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

#### FR-013-001: Core Capability
**Description**: The system shall provide the primary capability described in this feature specification.
**Acceptance Criteria**:
- The capability is available when the feature is enabled.
- The capability integrates with existing TSiSIP components (OpenSIPS, PostgreSQL, OCP) without regression.

#### FR-013-002: Configuration & Persistence
**Description**: All configuration changes shall be persisted to PostgreSQL and reflected in runtime behavior without requiring a full stack restart.
**Acceptance Criteria**:
- Configuration changes survive container restarts.
- Invalid configuration is rejected at the validation gate.

#### FR-013-003: Observability & Audit
**Description**: The feature shall emit metrics or audit events compatible with the TSiSIP Prometheus/Grafana and OCP audit logging pipelines.
**Acceptance Criteria**:
- Metrics or audit events are visible in the appropriate dashboard or log.
- Failure conditions are logged with sufficient context for debugging.


## Success Criteria

| ID | Criterion | Measurement | Target |
|---|---|---|---|
| SC-013-001 | Feature functional completeness | End-to-end validation test pass rate | 100% |
| SC-013-002 | Configuration persistence | Restart test with prior configuration | Pass |
| SC-013-003 | Zero regression in existing flows | Existing integration tests pass rate | 100% |
| SC-013-004 | Observability coverage | Metrics/audit events present | 100% of mutating actions |

