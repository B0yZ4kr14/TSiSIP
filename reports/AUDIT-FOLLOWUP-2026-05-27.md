# Audit Follow-Up Report — 2026-05-27

**Date**: 2026-05-27
**Commit**: `07582b9`
**Scope**: Verification and remediation of findings from consolidated audit 2026-05-26

---

## Executive Summary

| Report | Date | Findings | Resolved | Remaining |
|--------|------|----------|----------|-----------|
| Brownfield Scan | 2026-05-26 | 12 (0 CRITICAL, 1 HIGH, 5 MEDIUM, 6 LOW) | 10 | 2 LOW |
| Version Guard | 2026-05-26 | 6 HIGH, 6 MED/LOW | 6 | 0 |
| Memory Lint | 2026-05-26 | 3 CRITICAL, 4 HIGH, 5 MEDIUM, 2 LOW | 5 | 2 MEDIUM, 2 LOW |
| **Total** | | **38** | **21** | **4** |

---

## Brownfield Scan

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| B17 | HIGH | Copilot instructions stale | RESOLVED |
| B18 | MED | Plaintext password in seed SQL | RESOLVED |
| B19 | MED | VARCHAR(36) vs UUID | ACCEPTED (AD-024-4) |
| B20 | MED | Legacy password columns | DEFERRED |
| B21 | MED | Missing pipefail | RESOLVED (POSIX sh) |
| B22 | MED | Missing set -e in healthcheck | RESOLVED |
| B23 | LOW | Hard-coded test password | RESOLVED |
| B24 | LOW | env.example latest default | RESOLVED |
| B25 | LOW | EXPOSE 22222/udp | RESOLVED (not present) |
| B26 | LOW | EXPOSE 5038/tcp | RESOLVED (not present) |
| B27 | LOW | RTPengine loopback fallback | RESOLVED |
| B28 | LOW | TODO noise in .specify/ | RESOLVED |

---

## Version Guard

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| D9/R5 | HIGH | Admin API vs OCP PHP image | RESOLVED (same digest) |
| F1 | HIGH | env.example floating latest | RESOLVED (v0.0.0-dev) |
| A2 | HIGH | rtpengine APT unpinned | RESOLVED (10.5.3.5-1) |
| V18 | HIGH | Image tag floating | RESOLVED (:?must be set) |
| V19 | HIGH | rtpengine APT unpinned | RESOLVED |
| X2 | HIGH | Certbot registry prefix | RESOLVED |

---

## Memory Lint

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| M1 | CRIT | OpenSIPS over-allocation | RESOLVED (-m/-M set) |
| M2 | CRIT | PostgreSQL prod > 8GB | RESOLVED (work_mem 8MB) |
| M3 | HIGH | PostgreSQL shm_size | RESOLVED (3gb) |
| M4 | HIGH | PostgreSQL dev > reservation | RESOLVED |
| M5 | HIGH | Unbounded LGPD fetchAll | RESOLVED (LIMIT/OFFSET) |
| M6 | HIGH | Unbounded audit check | RESOLVED (LIMIT 1000) |
| M7 | MED | No explicit children | ACCEPTED (auto-detect) |
| M8 | MED | No connection pooler | RESOLVED (PgBouncer) |
| M9 | MED | No container memory alerts | RESOLVED (ContainerMemoryHigh) |
| M10 | MED | Host swap 130GB | DEFERRED (operator) |
| M11 | MED | RTPengine userspace memory | RESOLVED (alerting) |

---

## Remaining Deferred Work

- M10: Host swap tuning (operator action)
- B20: Legacy schema column cleanup (future sprint)
- DNS A record for domain (operator action)
- Firewall ACL for SIP ports (operator action)
- S3-compatible backup credentials (operator action)

---

## Validation

- GitNexus: Up-to-date (9,939 nodes, 10,912 edges, 81 clusters)
- Tests: 109 PASS, 0 FAIL
- Containers: 16/17 healthy
- Working tree: Clean
- Feature 020: 61/61 complete
