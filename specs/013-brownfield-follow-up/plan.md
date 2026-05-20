# Feature 013 Implementation Plan

## Wave 1: Backup Script Fix (Coder Agent)

Agent: `coder`

- [ ] W1.1: Fix docker/backup/backup.sh line 31 — replace conditional referencing removed ALLOW_UNENCRYPTED_BACKUPS with simple encryption key check.
- [ ] W1.2: Test backup.sh syntax with bash -n.
- [ ] W1.3: Verify script still enforces mandatory encryption (exits if key missing).

## Wave 2: Healthchecks — Backup Service (Coder Agent)

Agent: `coder`
Parallel with Wave 3.

- [ ] W2.1: Add healthcheck to backup service in docker-compose.yml.
- [ ] W2.2: Add healthcheck to backup service in docker-compose.prod.yml.
- [ ] W2.3: Add healthcheck to backup service in docker-compose.vps.yml.
- [ ] W2.4: Add healthcheck script to docker/backup/ if needed (simple cron lockfile age check).

## Wave 3: Healthchecks — Anomaly Detector (Coder Agent)

Agent: `coder`
Parallel with Wave 2.

- [ ] W3.1: Add healthcheck to anomaly-detector in docker-compose.yml.
- [ ] W3.2: Add healthcheck to anomaly-detector in docker-compose.prod.yml.
- [ ] W3.3: Verify anomaly-detector exposes HTTP endpoint suitable for healthcheck.

## Wave 4: CI Documentation (Docs Agent)

Agent: `docs`
Parallel with Waves 2-3.

- [ ] W4.1: Add comment in .github/workflows/deploy.yml explaining :latest is CI artifact only.
- [ ] W4.2: Update AGENTS.md CI section if needed.

## Wave 5: Security Review (Reviewer Agent)

Agent: `reviewer`
Depends on: W1, W2, W3.

- [ ] W5.1: Review backup.sh for security regressions.
- [ ] W5.2: Review healthcheck endpoints for data leakage.
- [ ] W5.3: Run scripts/ci-scan.sh and verify all gates pass.

## Wave 6: QA & Validation (QA Agent)

Agent: `qa`
Depends on: W5.

- [ ] W6.1: Validate docker compose config syntax for all three files.
- [ ] W6.2: Verify no services lack restart policies.
- [ ] W6.3: Confirm brownfield scan post-fix shows zero new findings.

## Wave 7: Commit & Close (Release Agent)

Agent: `release`
Depends on: W6.

- [ ] W7.1: Stage all changes.
- [ ] W7.2: Write conventional commit message.
- [ ] W7.3: Push to GitHub.
- [ ] W7.4: Update OMK Goal with evidence.
- [ ] W7.5: Close Feature 013.

## Dependency Graph

```
W1 (Backup Fix) ──┐
W2 (Backup HC) ───┼→ W5 (Security) → W6 (QA) → W7 (Commit)
W3 (Anomaly HC) ──┘
W4 (CI Docs) ─────┘ (non-blocking, parallel)
```
