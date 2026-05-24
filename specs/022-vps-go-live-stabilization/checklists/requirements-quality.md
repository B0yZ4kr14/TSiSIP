# Feature 022 — Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of requirements in spec.md and plan.md for Feature 022 (VPS Go-Live Stabilization).

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## Requirement Completeness

- [ ] CHK001 - Are failure-mode requirements defined for services that fail healthchecks (e.g., certbot restarting)? [Gap, Spec §AC1]
- [ ] CHK002 - Are TDD evidence requirements specified for all critical paths (SIP, HTTP, rollback)? [Completeness, Spec §AC2]
- [ ] CHK003 - Are performance/timeout thresholds defined for OCP response time ("within 5 seconds" is specified, but is this under load)? [Clarity, Spec §AC4]
- [ ] CHK004 - Are TLS certificate activation requirements and verification steps defined? [Gap, Spec §AC4]
- [ ] CHK005 - Is the evidence bundle structure and naming convention documented? [Completeness, Spec §AC7]
- [ ] CHK006 - Are plan compliance audit criteria (F1-F4) defined with specific checklists? [Gap, Spec §AC8]

## Requirement Clarity

- [ ] CHK007 - Is "healthy for >=10 minutes" quantified with specific probe intervals and success thresholds? [Clarity, Spec §AC1]
- [ ] CHK008 - Is "restart loop exceeding 5 restarts in 60 seconds" defined with a specific observation window and measurement method? [Clarity, Spec §AC1]
- [ ] CHK009 - Is "TDD RED→GREEN→REFACTOR cycle" defined with explicit pass/fail criteria per cycle? [Clarity, Spec §AC2]
- [ ] CHK010 - Is the SIP OPTIONS test defined with expected headers, response time limits, and retry policy? [Clarity, Spec §AC3]
- [ ] CHK011 - Is "executable without ambiguity" for the rollback runbook defined with a validation procedure? [Clarity, Spec §AC5]
- [ ] CHK012 - Is "zero public Asterisk/PostgreSQL ports" defined with a specific audit command and expected output? [Clarity, Spec §AC6]
- [ ] CHK013 - Is the "vps-lite" profile explicitly defined (which services are in/out)? [Clarity, Spec §Overview]

## Requirement Consistency

- [ ] CHK014 - Do AC1 (healthchecks) and AC6 (port audit) requirements align regarding certbot/certbot-exporter status expectations? [Consistency, Spec §AC1 vs AC6]
- [ ] CHK015 - Are security requirements (R1-R3) consistent with architecture decisions (AD-022-1 to AD-022-3)? [Consistency, Spec §Security vs AD]
- [ ] CHK016 - Does the "Out of Scope" section consistently exclude items that would otherwise appear in acceptance criteria? [Consistency, Spec §Out of Scope]
- [ ] CHK017 - Are test tools in plan.md (bash, sipsak, curl, Python 3) consistent with the TDD toolchain referenced in spec.md? [Consistency, Plan §Tech Stack vs Spec §AC2]

## Acceptance Criteria Quality

- [ ] CHK018 - Can AC1 (service health) be objectively measured with a single command? [Measurability, Spec §AC1]
- [ ] CHK019 - Can AC2 (TDD cycle) be objectively verified without subjective judgment? [Measurability, Spec §AC2]
- [ ] CHK020 - Can AC4 (OCP response) be verified programmatically (HTTP status + content + timing)? [Measurability, Spec §AC4]
- [ ] CHK021 - Can AC5 (rollback runbook) be objectively validated (e.g., by a second operator)? [Measurability, Spec §AC5]
- [ ] CHK022 - Can AC8 (plan compliance) be verified with an automated checklist or script? [Measurability, Spec §AC8]

## Scenario Coverage

- [ ] CHK023 - Are requirements defined for partial stack failure scenarios (e.g., only OpenSIPS down)? [Coverage, Gap]
- [ ] CHK024 - Are requirements defined for recovery after a failed rollback attempt? [Coverage, Gap]
- [ ] CHK025 - Are requirements defined for secrets file corruption/missing during bring-up? [Coverage, Gap]
- [ ] CHK026 - Are requirements defined for Docker network partition scenarios? [Coverage, Gap]
- [ ] CHK027 - Are requirements defined for concurrent deployment and rollback operations? [Coverage, Gap]

## Edge Case Coverage

- [ ] CHK028 - Are edge cases defined for healthcheck false positives (service reports healthy but is not functional)? [Edge Case, Gap]
- [ ] CHK029 - Are edge cases defined for evidence files exceeding size limits or containing binary data? [Edge Case, Gap]
- [ ] CHK030 - Are edge cases defined for DNS propagation delays affecting TLS certificate validation? [Edge Case, Spec §AC4]
- [ ] CHK031 - Are edge cases defined for VPS resource exhaustion (disk full, memory pressure) during stabilization? [Edge Case, Gap]

## Non-Functional Requirements

- [ ] CHK032 - Are performance requirements defined for stack bring-up time (target duration)? [NFR, Gap]
- [ ] CHK033 - Are observability requirements defined (log retention, metric collection, alerting)? [NFR, Gap]
- [ ] CHK034 - Are security hardening requirements beyond R1-R3 defined (e.g., container capabilities, seccomp)? [NFR, Gap]
- [ ] CHK035 - Are resource limit requirements defined for all vps-lite services (CPU, memory, disk)? [NFR, Gap]

## Dependencies & Assumptions

- [ ] CHK036 - Is the assumption that "secrets/ are already provisioned on VPS" validated with a pre-flight check? [Assumption, Spec §Context]
- [ ] CHK037 - Are Docker and Docker Compose version requirements specified as minimum versions? [Dependency, Gap]
- [ ] CHK038 - Are external dependency requirements documented (e.g., DNS provider for tsiapp.io)? [Dependency, Gap]
- [ ] CHK039 - Is the assumption of single-instance PostgreSQL (no HA) explicitly accepted and documented? [Assumption, Spec §Out of Scope]

## Ambiguities & Conflicts

- [ ] CHK040 - Is the term "production readiness" defined with specific measurable milestones? [Ambiguity, Spec §Overview]
- [ ] CHK041 - Is "coordinated 24-hour stabilization window" defined with phase boundaries and go/no-go criteria? [Ambiguity, Spec §Overview]
- [ ] CHK042 - Is "critical paths" in AC2 explicitly enumerated (which paths are critical)? [Ambiguity, Spec §AC2]
- [ ] CHK043 - Is there a conflict between AD-022-3 (evidence in .sisyphus/evidence/) and R1 (no secrets in evidence)? [Conflict, Spec §AD-022-3 vs R1]
