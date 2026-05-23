# Feature 013 Memory: Brownfield Residual Findings Remediation

## Current Scope
Remediate 3 residual brownfield findings identified after the 5-cycle brownfield remediation: backup script bug, missing healthchecks for anomaly-detector and backup services, and CI documentation gap.

## Relevant Decisions
- **Surgical fixes only**: No new features, no CI/CD redesign, no application logic changes.
- **Multi-agent wave execution**: Coder (Waves 1-3), Docs (Wave 4), Reviewer (Wave 5), QA (Wave 6), Release (Wave 7).
- **Zero new findings target**: Post-fix brownfield scan must show zero outstanding items.

## Active Architecture Constraints
- All CI gates (`scripts/ci-scan.sh`) must pass after changes.
- All services must have restart policies.
- Docker Compose config syntax must validate for all three compose files.

## Accepted Deviations
- None.

## Relevant Security Constraints
- `docker/backup/backup.sh` must fail fatally when encryption key is missing.
- Healthcheck endpoints must not leak sensitive data.
- `:latest` tag in CI workflows must be explicitly documented as CI artifact only, not for production.

## Related Historical Lessons
- Brownfield scans should run after every major feature to catch drift early.
- Healthcheck gaps are common in supporting services (backup, anomaly-detector) after initial foundation work.
- Removing a feature flag (`ALLOW_UNENCRYPTED_BACKUPS`) without cleaning up all references causes runtime errors.

## Conflict Warnings
- None.

## Retrieval Notes
- Search terms: brownfield, remediation, backup.sh, healthcheck, anomaly-detector, ci-scan.
- Related features: 004 (health checks), 005 (backup), 006 (anomaly detector).
