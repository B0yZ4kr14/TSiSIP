# Feature 013: Brownfield Residual Findings Remediation

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

- [ ] AC1: docker/backup/backup.sh runs without unbound variable error when encryption key is present.
- [ ] AC2: docker/backup/backup.sh fails fatally when encryption key is missing.
- [ ] AC3: docker-compose.yml has healthcheck on anomaly-detector and backup.
- [ ] AC4: docker-compose.prod.yml has healthcheck on anomaly-detector and backup.
- [ ] AC5: docker-compose.vps.yml has healthcheck on backup.
- [ ] AC6: CI scan (scripts/ci-scan.sh) passes after changes.
- [ ] AC7: All changes committed with conventional commits.

## References

- reports/brownfield-scan-2026-05-20-post-remediation.md
- evidence/remediation/ciclo-3/b8-backup-encryption-fix.md
