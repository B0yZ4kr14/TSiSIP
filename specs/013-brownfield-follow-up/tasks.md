# Feature 013 Tasks



## Phase 1: Backup Script Fix
- [x] T001: Fix docker/backup/backup.sh line 31 — remove ALLOW_UNENCRYPTED_BACKUPS reference
- [x] T002: Test syntax with bash -n docker/backup/backup.sh
- [x] T003: Verify script exits fatally if encryption key missing

## Phase 2: Backup Healthchecks
- [x] T004: Add healthcheck to docker-compose.yml backup service
- [x] T005: Add healthcheck to docker-compose.prod.yml backup service
- [x] T006: Add healthcheck to docker-compose.vps.yml backup service
- [x] T007: Create docker/backup/healthcheck.sh if needed

## Phase 3: Anomaly Detector Healthchecks
- [x] T008: Add healthcheck to docker-compose.yml anomaly-detector service
- [x] T009: Add healthcheck to docker-compose.prod.yml anomaly-detector service

## Phase 4: CI Documentation
- [x] T010: Add CI artifact comment to .github/workflows/deploy.yml

## Phase 5: Security Review
- [x] T011: Security review of backup.sh changes
- [x] T012: Security review of healthcheck endpoints
- [x] T013: Run scripts/ci-scan.sh

## Phase 6: QA Validation
- [x] T014: Validate docker compose config for all files
- [x] T015: Verify no missing restart policies
- [x] T016: Confirm zero brownfield findings post-fix

## Phase 7: Commit & Close
- [x] T017: Stage and commit changes
- [x] T018: Push to GitHub
- [x] T019: Update OMK Goal evidence
- [x] T020: Close Feature 013
