# Checklist: Requirements Quality — Feature 024: Brownfield Remediation

**Purpose**: Unit tests for the spec.md requirements. Validates quality, clarity, completeness, and readiness for implementation.
**Created**: 2026-05-24
**Depth**: Standard
**Audience**: Reviewer (PR)

---

## Requirement Completeness

- [ ] CHK001 — Are all 12 brownfield findings (B1–B12) explicitly traceable to at least one acceptance criterion? [Completeness, Gap]
- [ ] CHK002 — Does the spec define acceptance criteria for verifying that no NEW hard-coded IPs are introduced in future test scripts? [Completeness, Gap]
- [ ] CHK003 — Are requirements defined for how the env-example placeholder values should be validated (e.g., minimum length, format)? [Completeness, Spec AC5]
- [ ] CHK004 — Is there a requirement documenting how the HEALTHCHECK commands should behave when the service is intentionally down for maintenance? [Completeness, Gap]
- [ ] CHK005 — Are requirements specified for rollback if a SHA-pinned image digest becomes unavailable (registry outage)? [Completeness, Exception Flow]

## Requirement Clarity

- [ ] CHK006 — Is "SHA digest" defined with specific format criteria (e.g., 64-hex-char digest)? [Clarity, Spec AC1]
- [ ] CHK007 — Is "dynamic derivation via docker network inspect" specified with exact command syntax and expected output format? [Clarity, Spec AC4]
- [ ] CHK008 — Is "inline comments explaining the wait purpose" quantified with minimum comment length or required elements? [Clarity, Spec AC6]
- [ ] CHK009 — Is the definition of "passes" for the OCP healthcheck quantified (HTTP status code, response time threshold)? [Clarity, Spec AC7]
- [ ] CHK010 — Is "zero HIGH/MEDIUM findings" defined with the exact scan scope and baseline commit for the post-fix brownfield scan? [Clarity, Spec AC10]

## Requirement Consistency

- [ ] CHK011 — Are the security requirements (R1–R3) consistent with the acceptance criteria (e.g., R2 Trivy scan maps to AC1 verification)? [Consistency]
- [ ] CHK012 — Does AD-024-3 (HEALTHCHECK interval and retries) align with the actual verification step in T8 (inspect after 60s)? [Consistency, Spec AD-024-3 vs Plan T8]
- [ ] CHK013 — Are the Out of Scope items mutually exclusive with the acceptance criteria (no overlap)? [Consistency]
- [ ] CHK014 — Is the env-example placeholder policy (AD-024-2) consistent with the security requirement that no secrets be committed (R1)? [Consistency]

## Acceptance Criteria Quality

- [ ] CHK015 — Can AC1 be objectively verified without manual judgment (is the SHA pattern checkable via grep/regex)? [Measurability, Spec AC1]
- [ ] CHK016 — Can AC4 be verified in an environment without running Docker containers (e.g., static script analysis)? [Measurability, Spec AC4]
- [ ] CHK017 — Is AC9 (docker compose config validates) deterministic across environments, or does it depend on host-specific state? [Measurability, Spec AC9]
- [ ] CHK018 — Does AC10 define who performs the brownfield scan, which tool, and what constitutes "zero findings"? [Measurability, Spec AC10]

## Scenario Coverage

- [ ] CHK019 — Are requirements defined for the scenario where docker network inspect returns multiple gateways or no gateway? [Coverage, Exception Flow]
- [ ] CHK020 — Are requirements defined for the scenario where a sleep statement already has a comment but the comment is vague? [Coverage, Edge Case]
- [ ] CHK021 — Are requirements defined for partial remediation (e.g., only some findings resolved in a single PR)? [Coverage, Alternate Flow]
- [ ] CHK022 — Are requirements defined for verifying that the changes do not break existing CI pipelines? [Coverage, Gap]

## Edge Case Coverage

- [ ] CHK023 — Does the spec define behavior when the SHA-pinned image digest is deprecated or removed from the registry? [Edge Case, Spec AC1]
- [ ] CHK024 — Are edge cases addressed for env-example when compose references optional variables with default values? [Edge Case, Spec AC5]
- [ ] CHK025 — Is the behavior defined when a Dockerfile already has a HEALTHCHECK instruction that conflicts with the proposed one? [Edge Case, Spec AC8]
- [ ] CHK026 — Are requirements specified for handling sleep statements inside subshells or loops in deploy scripts? [Edge Case, Spec AC6]

## Non-Functional Requirements

- [ ] CHK027 — Are performance requirements defined for the dynamic IP discovery (e.g., maximum latency impact on deploy scripts)? [NFR, Gap]
- [ ] CHK028 — Are reliability requirements specified for the HEALTHCHECK additions (e.g., false-positive rate, startup grace period)? [NFR, Spec AD-024-3]
- [ ] CHK029 — Are maintainability requirements defined (e.g., how often should env-example be re-audited)? [NFR, Gap]

## Dependencies & Assumptions

- [ ] CHK030 — Is the assumption that Docker and docker compose are available on the target host explicitly documented? [Assumption, Gap]
- [ ] CHK031 — Is the dependency on the brownfield scan tool/version documented for AC10 verification? [Dependency, Spec AC10]
- [ ] CHK032 — Is the dependency on Trivy (for R2 verification) documented with minimum version? [Dependency, Spec R2]
- [ ] CHK033 — Is the assumption that all target Dockerfiles are in docker/service/Dockerfile path pattern validated? [Assumption, Spec AC8]

## Ambiguities & Conflicts

- [ ] CHK034 — Is the term "low-effort and high-value" (from Objective) defined with criteria for which LOW findings are in scope? [Ambiguity, Spec Objective]
- [ ] CHK035 — Does the spec clarify whether B11 (OCP healthcheck) remediation is conditional on actual failure, or mandatory regardless? [Ambiguity, Spec AC7]
- [ ] CHK036 — Is there potential conflict between AD-024-2 (placeholder values) and AD-024-3 (HEALTHCHECK using localhost), if localhost is not the correct bind address? [Conflict, Spec AD-024-2 vs AD-024-3]
- [ ] CHK037 — Is the boundary between Feature 024 and the Out of Scope items clearly demarcated to prevent scope creep during implementation? [Ambiguity, Spec Out of Scope]
