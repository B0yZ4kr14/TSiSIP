# Feature 013 Implementation Plan

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/013-brownfield-follow-up/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



## Wave 1: Backup Script Fix (Coder Agent)

Agent: `coder`

- [x] W1.1: Fix docker/backup/backup.sh line 31 — replace conditional referencing removed ALLOW_UNENCRYPTED_BACKUPS with simple encryption key check.
- [x] W1.2: Test backup.sh syntax with bash -n.
- [x] W1.3: Verify script still enforces mandatory encryption (exits if key missing).

## Wave 2: Healthchecks — Backup Service (Coder Agent)

Agent: `coder`
Parallel with Wave 3.

- [x] W2.1: Add healthcheck to backup service in docker-compose.yml.
- [x] W2.2: Add healthcheck to backup service in docker-compose.prod.yml.
- [x] W2.3: Add healthcheck to backup service in docker-compose.vps.yml.
- [x] W2.4: Add healthcheck script to docker/backup/ if needed (simple cron lockfile age check).

## Wave 3: Healthchecks — Anomaly Detector (Coder Agent)

Agent: `coder`
Parallel with Wave 2.

- [x] W3.1: Add healthcheck to anomaly-detector in docker-compose.yml.
- [x] W3.2: Add healthcheck to anomaly-detector in docker-compose.prod.yml.
- [x] W3.3: Verify anomaly-detector exposes HTTP endpoint suitable for healthcheck.

## Wave 4: CI Documentation (Docs Agent)

Agent: `docs`
Parallel with Waves 2-3.

- [x] W4.1: Add comment in .github/workflows/deploy.yml explaining :latest is CI artifact only.
- [x] W4.2: Update AGENTS.md CI section if needed.

## Wave 5: Security Review (Reviewer Agent)

Agent: `reviewer`
Depends on: W1, W2, W3.

- [x] W5.1: Review backup.sh for security regressions.
- [x] W5.2: Review healthcheck endpoints for data leakage.
- [x] W5.3: Run scripts/ci-scan.sh and verify all gates pass.

## Wave 6: QA & Validation (QA Agent)

Agent: `qa`
Depends on: W5.

- [x] W6.1: Validate docker compose config syntax for all three files.
- [x] W6.2: Verify no services lack restart policies.
- [x] W6.3: Confirm brownfield scan post-fix shows zero new findings.

## Wave 7: Commit & Close (Release Agent)

Agent: `release`
Depends on: W6.

- [x] W7.1: Stage all changes.
- [x] W7.2: Write conventional commit message.
- [x] W7.3: Push to GitHub.
- [x] W7.4: Update OMK Goal with evidence.
- [x] W7.5: Close Feature 013.

## Dependency Graph

```
W1 (Backup Fix) ──┐
W2 (Backup HC) ───┼→ W5 (Security) → W6 (QA) → W7 (Commit)
W3 (Anomaly HC) ──┘
W4 (CI Docs) ─────┘ (non-blocking, parallel)
```
