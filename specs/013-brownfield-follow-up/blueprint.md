# Blueprint — Brownfield Residual Findings Remediation

## Overview

Post-remediation validation scan identified 3 new findings after the 5-cycle brownfield remediation. This feature addresses all residual and new findings to achieve zero outstanding brownfield items.

## Requirements

1. **B14 — Fix backup script residual bug**: Remove orphaned `ALLOW_UNENCRYPTED_BACKUPS` reference in `docker/backup/backup.sh` line 31.
2. **B15 — Add missing healthchecks**: Add `healthcheck` blocks to `anomaly-detector` and `backup` services across all compose files.
3. **B16 — Document CI latest tag**: Add comment in CI workflow explaining that `:latest` is CI artifact only, not for production.

## Architecture

- **Stack**: Docker Compose v3.8, Bash, GitHub Actions CI.
- **Services Affected**: `backup`, `anomaly-detector`.
- **Files Affected**: `docker/backup/backup.sh`, `docker-compose.yml`, `docker-compose.prod.yml`, `docker-compose.vps.yml`, `.github/workflows/deploy.yml`.

## Implementation Plan

### Wave 1: Backup Script Fix
- Replace conditional referencing removed `ALLOW_UNENCRYPTED_BACKUPS` with simple encryption key check.
- Test syntax with `bash -n`.
- Verify script still enforces mandatory encryption (exits if key missing).

### Wave 2: Healthchecks — Backup Service
- Add healthcheck to backup service in `docker-compose.yml`.
- Add healthcheck to backup service in `docker-compose.prod.yml`.
- Add healthcheck to backup service in `docker-compose.vps.yml`.
- Add healthcheck script to `docker/backup/` (simple cron lockfile age check).

### Wave 3: Healthchecks — Anomaly Detector
- Add healthcheck to anomaly-detector in `docker-compose.yml`.
- Add healthcheck to anomaly-detector in `docker-compose.prod.yml`.
- Verify anomaly-detector exposes HTTP endpoint suitable for healthcheck.

### Wave 4: CI Documentation
- Add comment in `.github/workflows/deploy.yml` explaining `:latest` is CI artifact only.
- Update `AGENTS.md` CI section if needed.

### Wave 5: Security Review
- Review `backup.sh` for security regressions.
- Review healthcheck endpoints for data leakage.
- Run `scripts/ci-scan.sh` and verify all gates pass.

### Wave 6: QA & Validation
- Validate docker compose config syntax for all three files.
- Verify no services lack restart policies.
- Confirm brownfield scan post-fix shows zero new findings.

### Wave 7: Commit & Close
- Stage all changes; write conventional commit message; push to GitHub.
- Update OMK Goal with evidence; close Feature 013.

## Tasks

**Wave 1: Backup Script Fix**
- T1.1: Fix `docker/backup/backup.sh` line 31 — remove `ALLOW_UNENCRYPTED_BACKUPS` reference
- T1.2: Test syntax with `bash -n docker/backup/backup.sh`
- T1.3: Verify script exits fatally if encryption key missing

**Wave 2: Backup Healthchecks**
- T2.1: Add healthcheck to `docker-compose.yml` backup service
- T2.2: Add healthcheck to `docker-compose.prod.yml` backup service
- T2.3: Add healthcheck to `docker-compose.vps.yml` backup service
- T2.4: Create `docker/backup/healthcheck.sh` if needed

**Wave 3: Anomaly Detector Healthchecks**
- T3.1: Add healthcheck to `docker-compose.yml` anomaly-detector service
- T3.2: Add healthcheck to `docker-compose.prod.yml` anomaly-detector service

**Wave 4: CI Documentation**
- T4.1: Add CI artifact comment to `.github/workflows/deploy.yml`

**Wave 5: Security Review**
- T5.1: Security review of `backup.sh` changes
- T5.2: Security review of healthcheck endpoints
- T5.3: Run `scripts/ci-scan.sh`

**Wave 6: QA Validation**
- T6.1: Validate docker compose config for all files
- T6.2: Verify no missing restart policies
- T6.3: Confirm zero brownfield findings post-fix

**Wave 7: Commit & Close**
- T7.1: Stage and commit changes
- T7.2: Push to GitHub
- T7.3: Update OMK Goal evidence
- T7.4: Close Feature 013

## Validation

- AC1: `docker/backup/backup.sh` runs without unbound variable error when encryption key is present.
- AC2: `docker/backup/backup.sh` fails fatally when encryption key is missing.
- AC3: `docker-compose.yml` has healthcheck on anomaly-detector and backup.
- AC4: `docker-compose.prod.yml` has healthcheck on anomaly-detector and backup.
- AC5: `docker-compose.vps.yml` has healthcheck on backup.
- AC6: CI scan (`scripts/ci-scan.sh`) passes after changes.
- AC7: All changes committed with conventional commits.

## Risks & Dependencies

| Risk | Mitigation |
|---|---|
| Healthcheck script exposes sensitive info | Use non-authenticated readiness checks only |
| Backup script fix breaks existing backup flow | Test syntax and exit behavior before commit |
| Compose healthcheck syntax invalid for one file | Validate all three compose files with `docker compose config` |

**Dependencies**: Docker Compose ≥2.20; Bash; GitHub Actions CI pipeline.
