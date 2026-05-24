# Governed Planning Summary

**Feature**: 024 — Brownfield Remediation  
**Workflow**: architecture-guard governed-plan  
**Generated**: 2026-05-24  
**Constitution Versions**: governance 1.1.0, architecture current, security 1.0.0

---

## Memory Context

- **Status**: Synthesized
- **Source**: specs/024-brownfield-remediation/memory-synthesis.md
- **Key Constraints**:
  - Docker-first, PostgreSQL-only, secret hygiene, network isolation (from constitution.md)
  - Framework-Specific Rule: All Dockerfiles must use SHA-pinned base images
  - Test Scripts Rule: No hard-coded Docker network CIDR IPs
  - AD-003: Docker-First Runtime Delivery
  - AD-007: OCP Admin Tools with RBAC + CSRF
  - BUG-004: Health check assumptions can break on fresh deploy
  - Historical: Feature 021 eliminated all CRITICAL/HIGH findings; Feature 022 established canonical production runtime

## Security Review

- **Status**: Reviewed
- **Source**: specs/024-brownfield-remediation/security-constraints.md
- **Constraints Found**:
  - C1: Supply-Chain Integrity — T1 pins admin-api base image to SHA digest
  - C2: Test Script Information Disclosure — T2/T3 parameterize hard-coded IPs
  - C3: Deploy Script Resilience — T4 removes static IP defaults, fails closed
  - C4: Configuration Hygiene — T6 completes env-example documentation
  - C5: Healthcheck Endpoint Exposure — T8 uses lightweight readiness checks only
- **Warnings**:
  - SEC-024-01 (LOW): certbot-exporter HEALTHCHECK port assumption — verify actual port
  - SEC-024-02 (LOW): anomaly-detector HEALTHCHECK may be too heavy — use file-based probe
- **Sign-off**: Approved with 2 advisory findings (0 blocking)

## Architecture Review

- **Source**: specs/024-brownfield-remediation/architecture-violations.md
- **Violations**: 0 P0 blocking violations detected
- **Review Items**:
  - ARCH-P1-01 (REVIEW): Add start_period: 60s to T8 HEALTHCHECK instructions
  - ARCH-P1-02 (REVIEW): Document CI network pre-creation requirement for T4 dynamic IP discovery
- **Consistency Risks**:
  - R1: HEALTHCHECK additions must not conflict with existing compose-level healthchecks
  - R2: Dynamic IP discovery must not break unattended automation; fail-closed is correct but needs documentation
- **Drift Assessment**: No new services, networks, or modules proposed. No Constitution Update Proposal required.
- **Sign-off**: PASS with 2 REVIEW items

## Recommended Actions

1. **Address ARCH-P1-01**: Add `start_period: 60s` recommendation to T8 implementation notes for backup and anomaly-detector services.
2. **Address ARCH-P1-02**: Document that CI pipelines must pre-create Docker networks before running deploy scripts with dynamic IP discovery.
3. **Address SEC-024-01**: Verify certbot-exporter actual metrics port before finalizing Dockerfile HEALTHCHECK.
4. **Address SEC-024-02**: Prefer file-based or HTTP-based health probe for anomaly-detector rather than Python module import.
5. **Continue to /speckit.tasks phase**: Plan is approved for task generation and implementation.
6. **Durable Memory Preservation**: (Proactively triggered) Review the proposed memory entries in memory-capture-proposal.md for inclusion in docs/memory/ and .specify/memory/.

## Durable Memory Preservation

- **Status**: Proactively triggered
- **Source**: specs/024-brownfield-remediation/memory-capture-proposal.md
- **Proposed Entries**: 4
  - Entry 1 (REPEATABLE_PATTERN, MEDIUM): Dynamic Docker Network IP Discovery
  - Entry 2 (BUG_PATTERN, HIGH): Unpinned Docker Base Images in Ancillary Dockerfiles
  - Entry 3 (ARCHITECTURE_CONSTRAINT, MEDIUM): Dockerfile HEALTHCHECK Completeness
  - Entry 4 (REPEATABLE_PATTERN, LOW): Exhaustive env-example Audit
- **Action Required**: Review and approve memory entries for inclusion in durable memory files.

---

**Governed Plan Status**: ✅ APPROVED FOR IMPLEMENTATION  
**Conditions**: Address ARCH-P1-01 and SEC-024-01 before finalizing Docker HEALTHCHECK changes  
**Next Phase**: /speckit.tasks
