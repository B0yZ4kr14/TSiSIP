# Durable Memory: Security Drift Lessons (2026-05-21)

**Trigger**: speckit-drift with Security Governance preset
**Captured by**: Architecture Guard
**Status**: Active

## Lesson 1: CSRF Audit Must Cover ALL POST Endpoints

**Finding**: `web/change-password.php` lacked CSRF protection while `dispatcher.php`, `subscribers.php`, `trunk-providers.php`, and `trunk-dids.php` had it.

**Root Cause**: CSRF implementation was added incrementally per-feature without a project-wide endpoint audit.

**Decision**: All future PHP files with POST handling MUST include `require_once __DIR__ . '/common/csrf.php'` and `validateCsrfToken()` before any state change. Add this to `spec-validate.gate`.

**Applies to**: Any OCP feature with forms.

## Lesson 2: Constitution → DDL Traceability Gap

**Finding**: `security_constitution.md` §7 listed `ocp_password_changes` as mandatory, but no DDL existed.

**Root Cause**: Constitution was written during governance phase; schema updates were not back-propagated to DDL.

**Decision**: Any new mandatory audit table in `security_constitution.md` MUST have a corresponding DDL in `db/init/` within the same commit.

**Applies to**: All future security constitution updates.

## Lesson 3: Trivy ≠ Supply Chain Security

**Finding**: Trivy CVE scanning is present, but SBOM, VEX, and SLSA provenance are entirely absent.

**Root Cause**: Supply-chain security was scoped out of MVP; no follow-up task was created.

**Decision**: SBOM generation is now P1 for CI. VEX and SLSA follow in Q3 2026.

**Applies to**: All Docker-based features.

## Lesson 4: Feature-Scoped IDs Prevent Cross-Project Collisions

**Finding**: Flat `FR-XXX` IDs caused traceability failures during cross-project analysis.

**Root Cause**: Early specs used sequential numbering without feature scoping.

**Decision**: `FR-NNN-XXX` is now mandatory. CI gate will reject flat IDs.

**Applies to**: All specs from 018 onward; retrofit 001–017 in batches.
