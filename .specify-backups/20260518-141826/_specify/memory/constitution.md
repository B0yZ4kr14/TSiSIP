<!--
SYNC IMPACT REPORT
Version: 1.0.0 (no changes)
Modified principles: None
Added sections: Governance, Versioning, Amendment Procedure
Templates requiring updates:
  - .specify/templates/spec-template.md (aligned)
Follow-up TODOs: None
Last validated: 2026-05-17
-->

# TSiSIP Project Constitution

> Non-negotiable principles for all specifications, plans, and implementations.
>
> **Version**: 1.0.0
> **Ratified**: 2026-05-16
> **Last Amended**: 2026-05-17

## 1. Docker-First Delivery

**MUST**: All TSiSIP runtime components are delivered through project-owned Docker images.
**MUST NOT**: Document or implement bare-metal, VM-first, or host-package-installation runtime paths as canonical.

## 2. PostgreSQL-Only Persistence

**MUST**: Use PostgreSQL for all relational persistence.
**MUST**: Use db_postgres, PostgreSQL DSNs, and PostgreSQL DDL exclusively.
**MUST NOT**: Introduce MySQL, MariaDB, db_mysql, or alternative SQL dialects.

## 3. OpenSIPS 3.6 LTS Baseline

**MUST**: Reference only OpenSIPS modules, parameters, and functions documented for OpenSIPS 3.6 LTS.
**MUST**: Validate all OpenSIPS claims against official opensips.org documentation.
**MUST NOT**: Use sanity module (not present in OpenSIPS 3.6 LTS).
**MUST NOT**: Use Kamailio-only functions (auth_check, auth_challenge).

## 4. Edge Isolation & Backend Privacy

**MUST**: OpenSIPS is the only public SIP signaling endpoint (5060/udp, 5060/tcp).
**MUST**: RTPengine is the only public RTP endpoint (10000-20000/udp).
**MUST NOT**: Publish host ports for Asterisk or PostgreSQL.
**MUST NOT**: Expose RTPengine ng-control socket externally.
**MUST**: Asterisk and PostgreSQL reside on internal Docker networks only.

## 5. Authentication & Secrets

**MUST**: Store SIP Digest credentials as HA1 hashes only (ha1, ha1_sha256, ha1_sha512t256).
**MUST NOT**: Store or transport plaintext passwords.
**MUST**: Strip Authorization and Proxy-Authorization headers before backend relay.
**MUST**: Inject runtime secrets via Docker secrets or environment-templated config.
**MUST NOT**: Commit secrets, .env* (except .env.example), or private keys.

## 6. Topology Hiding

**MUST**: Use topology_hiding to conceal backend PBX IP addresses.
**MUST**: Use topology_hiding("C") as canonical baseline.
**MUST**: Remove untrusted inbound headers before routing.

## 7. RTP Relay

**MUST**: Relay media through RTPengine with SDP rewriting.
**MUST**: Use explicit rtpengine_offer(), rtpengine_answer(), rtpengine_delete().
**MUST NOT**: Use rtpengine_manage() as canonical baseline.
**MUST**: Bind RTPengine ng-control to internal Docker network address only.

## 8. Dynamic Data-Driven Routing

**MUST**: Derive dispatcher set from authenticated tenant-scoped PostgreSQL metadata.
**MUST NOT**: Hard-code dispatcher destination selection.

## 9. Documentation & Specification Quality

**MUST**: Specifications focus on WHAT and WHY, not HOW.
**MUST**: Requirements be testable and unambiguous.
**MUST**: Success criteria be measurable and technology-agnostic.
**MUST**: All claims be falsifiable and source-validated.

---

## Governance

### Amendment Procedure
1. Propose change as PR with rationale.
2. Validate against all active specs for conflicts.
3. Require approval from at least one architect-level reviewer.
4. Update LAST_AMENDED_DATE and bump version per semantic rules below.

### Versioning Policy
- MAJOR: Backward-incompatible governance or principle removals/redefinitions.
- MINOR: New principle added or materially expanded guidance.
- PATCH: Clarifications, wording, typo fixes, non-semantic refinements.

### Compliance Review
- All specs must pass constitution alignment check before /speckit-implement.
- Constitution conflicts are automatically CRITICAL and block implementation.
