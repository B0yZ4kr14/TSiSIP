# Feature 013 Memory Synthesis: Brownfield Residual Findings Remediation

## Current Scope
Fix 3 residual brownfield findings: backup script bug, missing healthchecks, CI documentation gap.

## Relevant Decisions
- Surgical fixes only; no new features.
- Multi-agent waves (coder, docs, reviewer, qa, release).
- Zero new findings target.

## Active Architecture Constraints
- All CI gates pass.
- All services have restart policies.
- All compose files validate.

## Accepted Deviations
None.

## Relevant Security Constraints
- backup.sh must fail fatally if encryption key missing.
- Healthchecks must not leak data.
- `:latest` documented as CI-only.

## Related Historical Lessons
- Run brownfield scans after every major feature.
- Supporting services often lack healthchecks post-foundation.
- Feature flag removal requires full reference cleanup.

## Conflict Warnings
None.

## Retrieval Notes
- Keywords: brownfield, remediation, backup.sh, healthcheck, ci-scan.
- Related: 004, 005, 006.
