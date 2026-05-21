# Feature 016 Checklist: Full Coverage — Requirements Quality Validation

> **Purpose**: Validate the quality, clarity, completeness, and consistency of Feature 016 requirements (spec.md, plan.md, tasks.md) before release sign-off.
> **Depth**: Formal release gate (~40 items)
> **Generated**: 2026-05-21
> **Authority**: `docs/TSiSIP-CANONICAL-SPEC.md`, `AGENTS.md`, Speckit checklist template

---

## Requirement Completeness

- [ ] CHK001 — Are all 15 canonical action codes (LOGIN, LOGOUT, PASSWORD_CHANGE, SUBSCRIBER_CREATE, SUBSCRIBER_UPDATE, SUBSCRIBER_TOGGLE, DISPATCHER_CREATE, DISPATCHER_UPDATE, DISPATCHER_DELETE, DISPATCHER_TOGGLE, CONFIG_VIEW, EXPORT_CSV, EXPORT_JSON, RETENTION_RUN) explicitly defined with their triggering conditions and semantics? [Completeness, Spec §AC2]
- [ ] CHK002 — Are the exact column constraints (NULL/NOT NULL, defaults, data types) specified for every field in `ocp_audit_log`, including edge cases like `user_id` for unauthenticated attempts? [Completeness, Spec §AC1]
- [ ] CHK003 — Are requirements defined for what happens when the hash chain computation fails during `logAuditEvent()` insertion (e.g., DB race condition, connection loss)? [Gap]
- [ ] CHK004 — Are requirements defined for audit logging during PostgreSQL connection failures or transaction rollbacks? [Coverage, Exception Flow, Gap]
- [ ] CHK005 — Are the exact HTTP response codes and error messages specified for `audit-export.php` when the user lacks `devops` role or is unauthenticated? [Gap]
- [ ] CHK006 — Are requirements defined for the zero-state scenario (empty audit log on fresh install) and how the dashboard behaves? [Coverage, Edge Case, Gap]
- [ ] CHK007 — Are requirements defined for audit log behavior when OCP is running in a read-only or maintenance mode? [Gap]
- [ ] CHK008 — Are upgrade/migration requirements defined for environments that already have `ocp_login_log` data? [Completeness, Gap]

## Requirement Clarity

- [ ] CHK009 — Is "append-only" explicitly defined as "no UPDATE or DELETE except via the retention purge function executed by the `tsisip_retention` role"? [Clarity, Spec §AC1]
- [ ] CHK010 — Is "configurable retention" quantified with a specific default value (90 days), an allowed range, and a maximum enforceable limit? [Clarity, Spec §AC5]
- [ ] CHK011 — Is "resilient error handling" in `logAuditEvent()` quantified with concrete behavior (e.g., max retry count, fallback to file log, circuit breaker)? [Ambiguity, Spec §AC2]
- [ ] CHK012 — Is "stream output" in `audit-export.php` defined with a concrete memory ceiling, row batch size, or timeout to prevent resource exhaustion? [Ambiguity, Spec §AC4]
- [ ] CHK013 — Is "tenant-scoped" clarified for audit log queries in multi-tenant deployments where `tenant_id` is not a column in `ocp_audit_log`? [Gap]
- [ ] CHK014 — Is the term "immutability guarantee" defined with a measurable predicate that can be verified without manual SQL inspection? [Clarity, Spec §AC6]
- [ ] CHK015 — Is the hash chain "canonical concatenation" explicitly defined (field order, encoding, separator) so independent validators produce identical hashes? [Clarity, Spec §AC1]

## Requirement Consistency

- [ ] CHK016 — Are the role requirements consistent between `audit-log.php` (`requireRole('devops')`) and the role hierarchy defined in `config.php` (`admin` > `devops`)? [Consistency, Spec §AC7]
- [ ] CHK017 — Do the export requirements (no pagination limit) align with the dashboard pagination constraints (default 50, max 200) without creating ambiguity about what "full result set" means? [Consistency, Spec §AC3 vs AC4]
- [ ] CHK018 — Is the `details` JSONB schema consistent across all action codes (e.g., does `SUBSCRIBER_CREATE` use the same key naming convention as `DISPATCHER_CREATE`)? [Consistency, Spec §AC2]
- [ ] CHK019 — Are the Docker-first requirements in AC8 consistent with the existing OCP Dockerfile patterns (e.g., cron installation method, log directory ownership)? [Consistency, Spec §AC8 vs Existing Dockerfile]
- [ ] CHK020 — Is the immutability trigger's exception message ('Audit log entries are immutable') consistent with the error logging strategy in `logAuditEvent()` (which uses `error_log()`)? [Consistency, Spec §AC1 vs AC2]

## Acceptance Criteria Quality

- [ ] CHK021 — Can the immutability guarantee be objectively verified by an automated test (not just manual SQL)? [Measurability, Spec §AC6]
- [ ] CHK022 — Is the hash chain continuity criterion defined with a measurable predicate (e.g., "for every row N > 1, row[N].prev_hash == row[N-1].hash")? [Measurability, Spec §AC6]
- [ ] CHK023 — Can the retention purge behavior be measured and verified (e.g., "after running purge with retention_days=90, zero rows older than 90 days remain")? [Measurability, Spec §AC5]
- [ ] CHK024 — Are the performance expectations for `audit-log.php` query latency quantified under specific dataset sizes (e.g., < 100ms for 1M rows with GIN index)? [Measurability, Gap]
- [ ] CHK025 — Is the CSV export UTF-8 BOM requirement measurable without Excel (e.g., hex signature verification)? [Measurability, Spec §AC4]

## Scenario Coverage

- [ ] CHK026 — Are requirements defined for concurrent `logAuditEvent()` INSERTs racing on hash chain computation (same-millisecond, same user)? [Coverage, Edge Case, Gap]
- [ ] CHK027 — Are requirements defined for retention purge interruption (partial deletion, crash mid-purge, restart)? [Coverage, Exception Flow, Gap]
- [ ] CHK028 — Are requirements defined for audit export when the filtered result set exceeds available PHP memory even with streaming? [Coverage, Exception Flow, Gap]
- [ ] CHK029 — Are requirements defined for the scenario where `HTTP_X_FORWARDED_FOR` contains multiple IPs (proxy chain)? [Coverage, Edge Case, Spec §AC2]
- [ ] CHK030 — Are requirements defined for audit logging of failed authentication attempts by unauthenticated users (brute-force scenarios)? [Coverage, Spec §AC2]
- [ ] CHK031 — Are requirements defined for the scenario where the `tsisip_retention` role does not exist during schema initialization? [Coverage, Exception Flow, Gap]

## Edge Case Coverage

- [ ] CHK032 — Are requirements defined for `details` JSONB exceeding PostgreSQL's 1GB limit per row (e.g., massive export metadata)? [Edge Case, Gap]
- [ ] CHK033 — Are requirements defined for clock skew or `event_time` collisions when multiple application servers write to the same audit log? [Edge Case, Gap]
- [ ] CHK034 — Are requirements defined for the `prev_hash` of the very first row (NULL) and how the hash chain is bootstrapped? [Edge Case, Spec §AC1]
- [ ] CHK035 — Are requirements defined for timezone handling in `event_time` (TIMESTAMPTZ) vs the `from`/`to` filter parameters (which may be naive dates)? [Edge Case, Spec §AC3]

## Non-Functional Requirements

- [ ] CHK036 — Are performance requirements defined for `audit-log.php` query latency under large datasets (e.g., 1M+ rows)? [NFR, Gap]
- [ ] CHK037 — Are storage requirements estimated for 90-day retention at projected event rates (e.g., events/day × row size × 90)? [NFR, Gap]
- [ ] CHK038 — Are backup and disaster recovery requirements defined for `ocp_audit_log` (e.g., should it be included in `pg_dump`, excluded, or replicated separately)? [NFR, Gap]
- [ ] CHK039 — Are monitoring/alerting requirements defined for audit log health (e.g., alert if `logAuditEvent()` failure rate > 1%)? [NFR, Gap]

## Dependencies & Assumptions

- [ ] CHK040 — Is the assumption that cron is available inside the OCP container documented and validated against the base image (`php:8.2-apache-bookworm`)? [Assumption, Spec §AC5]
- [ ] CHK041 — Is the assumption that `opensips` PostgreSQL user can be restricted to INSERT/SELECT without breaking existing OCP functionality documented? [Assumption, Spec §AC1]
- [ ] CHK042 — Are external compliance framework mappings (SOC 2, GDPR Art. 5(1)(e), PCI-DSS v4.0 10.2/10.3) validated against actual control requirements and not just referenced? [Dependency, Spec §Compliance Notes]
- [ ] CHK043 — Is the dependency on `common/pagination.php` helpers explicitly documented, including the required `perPage` ceiling change (100 → 200)? [Dependency, Spec §AC3]

## Ambiguities & Conflicts

- [ ] CHK044 — Is "searchable" in the compliance dashboard defined with specific search semantics (prefix, substring, full-text, case sensitivity) for each filter field? [Ambiguity, Spec §AC3]
- [ ] CHK045 — Is "one-click export" defined with a maximum response time or async behavior expectation? [Ambiguity, Spec §AC4]
- [ ] CHK046 — Is the conflict between "no pagination limit on export" and "cap export at 10,000 rows if unbuffered is problematic" resolved with a single canonical rule? [Conflict, Spec §AC4]
- [ ] CHK047 — Is the relationship between `ocp_login_log` (existing) and `ocp_audit_log` (new) clarified—does the spec require dual-writing or migration? [Ambiguity, Spec §AC2 vs Non-Goal #4]
