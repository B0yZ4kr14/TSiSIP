# Memory Synthesis — Feature 024: Brownfield Remediation

---

## Current Scope

Address B1–B12 findings from the post-Feature-022 brownfield scan. Focus on supply-chain determinism, test script hygiene, deploy script robustness, and configuration completeness.

## Relevant Decisions

- AD-003: Docker-First Runtime Delivery — base images must be SHA-pinned
- AD-007: OCP Admin Tools with RBAC + CSRF — admin-api Dockerfile is part of control plane
- CUP-012-01: Permit OCP Writes — subscriber proxy layer validated in Feature 023

## Active Architecture Constraints

- Docker-first, PostgreSQL-only, secret hygiene, network isolation
- Framework-Specific Rule: Dockerfiles must use SHA-pinned base images
- Test Scripts Rule: No hard-coded Docker network CIDR IPs
- env-example must document every referenced variable in compose

## Accepted Deviations

None for this feature. All findings are within-scope remediation.

## Relevant Security Constraints

- Supply chain: SHA-pinned base images prevent tag hijacking
- Test isolation: Hard-coded IPs couple tests to specific Docker network topology
- Secret hygiene: env-example must not contain real secrets, only placeholders

## Related Historical Lessons

- Feature 021 eliminated CRITICAL/HIGH findings from an earlier brownfield scan
- Feature 022 established docker-compose.vps.yml as canonical production runtime
- BUG-004: Health check assumptions can break on fresh deploy — relevant for B12

## Conflict Warnings

None. Remediation aligns with all Constitution rules.

## Retrieval Notes

- Search: brownfield, remediation, sha-pin, hard-coded-ip, env-example, healthcheck
- Related: Feature 021, 022, reports/brownfield-scan-report.md
