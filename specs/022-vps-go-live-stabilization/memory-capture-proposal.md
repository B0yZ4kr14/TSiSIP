# Memory Capture Proposal — Feature 022: VPS Go-Live Stabilization

**Generated**: 2026-05-23
**Proposed by**: governed-plan workflow (architecture-guard)
**Status**: Pending approval

---

## Proposed Memory Entries

### Entry 1: OpenSIPS sql_query Return Code Behavior

**Type**: BUG_PATTERN
**Severity**: HIGH
**Feature**: 022

**Lesson**: OpenSIPS `sql_query()` returns `-2` for empty result sets, not `false`. The pattern `if (!sql_query(...))` incorrectly treats empty results as errors. Always initialize AVPs to `0` before queries when used in subsequent non-null comparisons, because `NULL != 0` evaluates to `true` in OpenSIPS script.

**Evidence**: opensips/opensips.cfg.tpl (8 SELECT queries fixed)
**Related**: Feature 022 P0 bugfix, AGENTS.md §10 (Rejected Patterns)

### Entry 2: Dialplan Module Schema Rigidity

**Type**: ARCHITECTURE_CONSTRAINT
**Severity**: HIGH
**Feature**: 022

**Lesson**: OpenSIPS 3.6 `dialplan.so` requires the exact stock PostgreSQL schema including `dpid`, `disabled`, and `timerec` columns. Custom schemas cause fatal `db_do_query` errors on startup. Always generate stock schema first, then `ALTER TABLE` for extensions.

**Evidence**: db/init/04-ocp-tools-schema.sql (aligned to version 5)
**Related**: AGENTS.md §10, architecture_constitution.md §Contracts and Validation

### Entry 3: Python Global Variable Declaration in Container Lifecycle

**Type**: BUG_PATTERN
**Severity**: MEDIUM
**Feature**: 022

**Lesson**: Python functions that both read and write module-level variables must declare `global` at the start of the function. Missing `global` declarations cause `UnboundLocalError` when the variable is assigned after being read in a `try/except` block.

**Evidence**: docker/certbot-exporter/exporter.py (update_metrics() fix)
**Related**: SEC-022-01 (container hardening)

### Entry 4: TDD-First Infrastructure Stabilization Pattern

**Type**: REPEATABLE_PATTERN
**Severity**: MEDIUM
**Feature**: 022

**Lesson**: A 24h TDD-first go-live window can be executed with lightweight tools (bash, sipsak, curl, Python UDP probes) rather than heavy test frameworks. The RED->GREEN->REFACTOR cycle applies to infrastructure: RED tests prove the environment is broken before fixes, GREEN confirms fixes work, REFACTOR hardens without changing behavior.

**Evidence**: .sisyphus/evidence/task-* files, scripts/test-invite-407.sh
**Related**: constitution.md §Testing Expectations, plan.md §Execution Waves

### Entry 5: DNS as External Dependency for TLS

**Type**: ARCHITECTURE_CONSTRAINT
**Severity**: MEDIUM
**Feature**: 022

**Lesson**: Let's Encrypt certificate provisioning requires a valid DNS A record pointing to the VPS public IP before certbot can complete ACME challenges. Staging mode (`CERTBOT_STAGING=1`) is expected to loop until DNS is configured. This must be documented in the operator runbook as a pre-flight check.

**Evidence**: Active issue: DNS A Record Required
**Related**: security_constitution.md §TLS Requirements, deploy/README-VPS-DEPLOY.md

---

## Approval Request

These entries are proposed for inclusion in:
- `.specify/memory/BUGS.md` (Entries 1, 3)
- `.specify/memory/DECISIONS.md` (Entries 2, 4, 5)
- `docs/memory/BUGS.md` (mirror)

**Rationale**: Each lesson is repeatable and actionable. Entry 1 and 2 prevent P0 regressions. Entry 3 is a general Python pattern. Entry 4 establishes a lightweight infrastructure TDD pattern. Entry 5 documents an external dependency that has caused active issues.
