# Memory Capture Proposal — Feature 024: Brownfield Remediation

## Proposal Status

**Proposed**: 2026-05-24  
**Source**: Governed planning process for Feature 024  
**Action Required**: Review and approve entries for inclusion in durable memory

---

## Proposed Entries

### Entry 1: REPEATABLE_PATTERN — Dynamic Docker Network IP Discovery
- **Category**: REPEATABLE_PATTERN
- **Severity**: MEDIUM
- **Pattern**: Use `docker network inspect <network_name> --format='{{(index .IPAM.Config 0).Gateway}}'` instead of hard-coding RFC1918 IPs in deploy/test scripts.
- **Rationale**: Docker network gateways change on recreation. Static IPs cause silent routing failures.
- **Files to Update**: docs/memory/DECISIONS.md (add AD-024-1), .specify/memory/architecture_constitution.md (Framework-Specific Rules)
- **Proposed Text**:
  > AD-024-1: Dynamic IP Discovery for Deploy Scripts
  > - Date: 2026-05-24
  > - Context: Hard-coded 172.x IPs in deploy scripts break after Docker network recreation
  > - Decision: Derive gateway IPs dynamically via docker network inspect; fail closed on error
  > - Status: Active (L3 Decision)

### Entry 2: BUG_PATTERN — Unpinned Docker Base Images in Ancillary Dockerfiles
- **Category**: BUG_PATTERN
- **Severity**: HIGH
- **Pattern**: Main Dockerfile uses SHA-pinned base image, but ancillary service Dockerfiles (admin-api, backup, etc.) may use unpinned tags.
- **Rationale**: Supply-chain determinism requires ALL Dockerfiles to use SHA pinning, not just the primary one.
- **Files to Update**: docs/memory/BUGS.md
- **Proposed Text**:
  > BUG-008: Unpinned Base Image in Ancillary Dockerfile
  > - Date: 2026-05-24
  > - Severity: HIGH
  > - Symptom: admin-api/Dockerfile used php:8.2-apache without digest
  > - Root Cause: Focus on main OpenSIPS Dockerfile during Feature 001; ancillary Dockerfiles were not audited
  > - Fix: Pin to SHA digest in Feature 024
  > - Prevention: Add CI gate that greps all Dockerfiles for @sha256: pattern

### Entry 3: ARCHITECTURE_CONSTRAINT — Dockerfile HEALTHCHECK Completeness
- **Category**: ARCHITECTURE_CONSTRAINT
- **Severity**: MEDIUM
- **Pattern**: Compose-level healthchecks exist, but Dockerfile-level HEALTHCHECK instructions are missing for some services.
- **Rationale**: Dockerfile HEALTHCHECK enables standalone image health validation and signals orchestrators.
- **Files to Update**: docs/memory/ARCHITECTURE.md, .specify/memory/architecture_constitution.md
- **Proposed Text**:
  > Add to Framework-Specific Architecture Rules:
  > - All service Dockerfiles must declare a HEALTHCHECK instruction with interval >= 30s and start_period >= 60s.

### Entry 4: REPEATABLE_PATTERN — Exhaustive env-example Audit
- **Category**: REPEATABLE_PATTERN
- **Severity**: LOW
- **Pattern**: Use `grep -oP '\$\{\K[^}]+' docker-compose.vps.yml | sort -u` to generate the complete list of variables for env-example.
- **Rationale**: Manual audits miss variables; automated extraction ensures completeness.
- **Files to Update**: docs/memory/WORKLOG.md
- **Proposed Text**:
  > 2026-05-24: Automated env-example completeness check using grep extraction of compose variables.

---

## Approval Instructions

To approve these entries:
1. Edit the target files listed above.
2. Remove this proposal file or mark it APPROVED.
3. Commit with message: `docs(memory): capture Feature 024 lessons`

To reject an entry:
1. Strike through the entry in this file.
2. Add rejection rationale below the entry.
3. Commit the updated proposal file.
