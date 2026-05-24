<!--
SYNC IMPACT REPORT
Version change: none → 1.1.0 (minor bump)
Added sections:
  - Constitution Check Gates (new, plan-template.md alignment)
  - Brownfield Hygiene subsection under Governance
Modified principles: None renamed
Removed sections: None
Templates requiring updates:
  plan-template.md — Constitution Check gates now defined in constitution
  spec-template.md — no changes required
  tasks-template.md — no changes required
  checklist-template.md — no changes required
Follow-up TODOs: None
-->

# Project Constitution — TSiSIP

> Governance document for Architecture Guard and agent orchestration.

## Project Identity

- **Name**: TSiSIP
- **Type**: Infrastructure Platform (Docker-first SIP Edge Proxy)
- **Primary Stack**: OpenSIPS 3.6 LTS + PostgreSQL + RTPengine + Asterisk + PHP 8.2 (OCP)

## Engineering Philosophy

1. **Docker-image-first**: All runtime components must be delivered as project-owned Docker images. Bare-metal or VM-first installations are rejected.
2. **PostgreSQL-only**: OpenSIPS auth, routing, and tenant metadata use PostgreSQL exclusively. MySQL/MariaDB variants are forbidden.
3. **Security boundary**: OpenSIPS is the only public SIP entry point. Asterisk and PostgreSQL must have zero host-published ports.
4. **Precomputed HA1**: Subscriber credentials store HA1 hashes only (calculate_ha1 = 0). Plaintext passwords are never stored.
5. **Topology hiding**: Backend PBX IP addresses must never leak to the public internet via topology_hiding("C").
6. **Explicit RTP management**: Use rtpengine_offer(), rtpengine_answer(), rtpengine_delete() — not rtpengine_manage() as baseline.
7. **Spec-driven changes**: All features require spec.md + plan.md + tasks.md before implementation.

## Constitution Check Gates

Every implementation plan must pass these gates before Phase 0 research:

| Gate | Validation | Failure Action |
|---|---|---|
| Docker-first | No bare-metal or VM-first runtime paths proposed | Block until corrected |
| PostgreSQL-only | No db_mysql, db_sqlite, or MySQL/MariaDB references | Block until corrected |
| Module validity | Only OpenSIPS 3.6 LTS documented modules | Block until corrected |
| Secret hygiene | No plaintext secrets in proposed changes | Block until corrected |
| Network isolation | Asterisk and PostgreSQL have no host-published ports | Block until corrected |

Re-check after Phase 1 design if the plan introduces new services, networks, or dependencies.

## Security Expectations

- Public entry points (5060/udp, 5060/tcp) must authenticate all non-OPTIONS requests against PostgreSQL-backed subscriber credentials.
- Secrets (secrets/ directory, .env*, runtime credentials) must never be committed.
- TLS v1.2+ only; SRTP for media relay; mTLS for trusted SIP trunk endpoints.
- Header sanitization: strip Authorization, Proxy-Authorization, and untrusted routing headers before forwarding.
- Docker containers must run with cap_drop: [ALL] and minimal cap_add.

## Testing Expectations

- New OpenSIPS config changes must validate with opensips -c.
- New Docker images must pass healthchecks before deployment.
- Runtime SIP validation: OPTIONS 200 OK, INVITE 407 Proxy Authentication Required.
- Integration tests must cover auth, routing, and failover paths.

## Documentation Standards

- Architecture decisions are documented in docs/TSiSIP-CANONICAL-SPEC.md.
- Agent orchestration rules live in AGENTS.md and .specify/memory/agent-governance.md.
- All specs live in specs/{NNN-feature-name}/ with spec.md, plan.md, tasks.md.
- Runbooks and operator guidance belong in docs/TSiSIP-OPERATOR-RUNBOOK.md.

## Review Process

- P0 findings (security, auth contract, topology leaks) block release until resolved.
- Non-blocking architecture drift becomes tracked refactor work in .specify/memory/.
- Changes to docs/TSiSIP-CANONICAL-SPEC.md require multi-agent validation per docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md.
- Governance changes require explicit approval through the Socratic/Popperian review swarm.

## High-Level Architecture Intent

- **Architecture style**: Microservices (Docker Compose orchestration, 3-network topology)
- **Consistency goal**: Every container is independently replaceable; state lives in PostgreSQL and volumes only.
- **Evolution policy**: Use Constitution Update Proposals for new architecture standards. Spec Kit extensions (Architecture Guard, Spec Validate) enforce drift detection.

## Accepted Deviations

- The cdr table uses stock OpenSIPS schema with TSiSIP extensions; custom CREATE TABLE replacing stock schema is rejected.
- cachedb_local replaces htable (module absent in OpenSIPS 3.6 source tree).
- OCP v9 PHP frontend uses stubs; production may migrate to full OCP v9 source later.

## Governance and Evolution Policy

- Governance changes require explicit approval from the solution-architecture agent.
- Architecture enforcement changes target .specify/memory/architecture_constitution.md.
- Repeated drift triggers an Architecture Constitution Update Proposal via /speckit.architecture-guard.init.

### Brownfield Hygiene

- Brownfield scans run non-destructively against the canonical spec and AGENTS.md.
- Findings are tracked by severity (CRITICAL, HIGH, MEDIUM, LOW) with cycle-based remediation.
- Each remediation cycle must include: (a) fix, (b) evidence in a cycle-specific subdirectory under evidence/remediation/ (e.g., ciclo-1/, feature-013/), (c) post-fix validation scan.
- Residual bugs introduced during remediation are treated as P0 regressions.

## Infrastructure Configuration Rules

### Docker Compose
- **RTPengine control socket**: Must bind to `${RTPENGINE_INTERNAL_IP}:22222` on `sip_internal` only. Binding to `0.0.0.0` or `127.0.0.1` is forbidden (AGENTS.md Section 4, Section 10).
- **Container user context**: Services running with `cap_drop: [ALL]` that require filesystem ownership changes must specify `user` matching the container's expected runtime UID (e.g., postgres `user: "999:999"`).
- **Published ports whitelist**: Only OpenSIPS (5060/udp, 5060/tcp) and RTPengine (10000-20000/udp) may publish host ports. All other services must remain on internal networks only.

### Certificate Management
- **Self-signed cert rotation**: `secrets/server.crt` and `secrets/ca.crt` must be monitored for expiry. Alert threshold: 90 days before expiry.
- **Rotation evidence**: Every cert rotation must produce an evidence file in `docs/security/evidence/{feature-dir}/` with before/after fingerprint and validation command output.

### Test Scripts
- **No hard-coded network IPs**: Test scripts must not embed Docker network CIDR IPs (e.g., `172.19.0.4`). Use parameterized `TEST_IP` with dynamic discovery or compose network inspection.

## Blocking vs Non-Blocking Rules

**Blocking (P0)**:
- Security-sensitive changes cannot merge without security-review agent sign-off.
- OpenSIPS config must pass opensips -c validation.
- Docker Compose must not expose Asterisk or PostgreSQL ports publicly.
- Auth contract must use precomputed HA1 (calculate_ha1 = 0).
- RTPengine control socket must not bind to `0.0.0.0` or loopback in multi-container runtime.
- Certificates must not expire without documented rotation plan.

**Non-Blocking**:
- CSS/frontend cosmetic changes.
- Documentation wording improvements (unless canonical spec).
- Log message formatting.

## Notes

- This Constitution is versioned alongside the codebase.
- Update it through a Constitution Update Proposal when patterns evolve.
- Architecture Guard uses this document for governance, `architecture_constitution.md` for architecture enforcement, and `security_constitution.md` for security standards.

---

**Version**: 1.1.0 | **Ratified**: 2026-05-17 | **Last Amended**: 2026-05-20
