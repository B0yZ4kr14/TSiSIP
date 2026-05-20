# Feature 013 Tasks

## Wave 1: Backup Script Fix
- [ ] T1.1: Fix docker/backup/backup.sh line 31 — remove ALLOW_UNENCRYPTED_BACKUPS reference
- [ ] T1.2: Test syntax with bash -n docker/backup/backup.sh
- [ ] T1.3: Verify script exits fatally if encryption key missing

## Wave 2: Backup Healthchecks
- [ ] T2.1: Add healthcheck to docker-compose.yml backup service
- [ ] T2.2: Add healthcheck to docker-compose.prod.yml backup service
- [ ] T2.3: Add healthcheck to docker-compose.vps.yml backup service
- [ ] T2.4: Create docker/backup/healthcheck.sh if needed

## Wave 3: Anomaly Detector Healthchecks
- [ ] T3.1: Add healthcheck to docker-compose.yml anomaly-detector service
- [ ] T3.2: Add healthcheck to docker-compose.prod.yml anomaly-detector service

## Wave 4: CI Documentation
- [ ] T4.1: Add CI artifact comment to .github/workflows/deploy.yml

## Wave 5: Security Review
- [ ] T5.1: Security review of backup.sh changes
- [ ] T5.2: Security review of healthcheck endpoints
- [ ] T5.3: Run scripts/ci-scan.sh

## Wave 6: QA Validation
- [ ] T6.1: Validate docker compose config for all files
- [ ] T6.2: Verify no missing restart policies
- [ ] T6.3: Confirm zero brownfield findings post-fix

## Wave 7: Commit & Close
- [ ] T7.1: Stage and commit changes
- [ ] T7.2: Push to GitHub
- [ ] T7.3: Update OMK Goal evidence
- [ ] T7.4: Close Feature 013
