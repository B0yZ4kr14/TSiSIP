# Feature 022 — Architecture Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of architecture requirements in spec.md and plan.md for Feature 022.

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## Layer Boundaries

- [ ] CHK001 - Are layer boundary requirements explicitly verified for all 6 layers (Edge, Media, Database, PBX, Control Plane, Observability)? [Completeness, architecture_constitution §Layer Boundaries]
- [ ] CHK002 - Is the "no bypass" rule (no direct public SIP to Asterisk) explicitly tested? [Coverage, architecture_constitution §Business Logic Placement]
- [ ] CHK003 - Are cross-layer data access rules (SQL schema only, no filesystem) verified? [Consistency, architecture_constitution §Data Access Rules]

## Network Topology

- [ ] CHK004 - Is the 3-network topology (sip_edge, sip_internal, db_internal) explicitly verified in T6? [Clarity, architecture_constitution §Network Segmentation]
- [ ] CHK005 - Are network assignments for each vps-lite service explicitly documented? [Completeness, Gap]
- [ ] CHK006 - Is metrics_host network assignment verified for observability services? [Coverage, Gap]
- [ ] CHK007 - Are published ports explicitly listed per service with justification? [Clarity, AC6]

## Docker Contracts

- [ ] CHK008 - Are project-owned image requirements verified (no external registry dependencies)? [Completeness, architecture_constitution §Contracts]
- [ ] CHK009 - Are base image SHA256 digests pinned in all Dockerfiles? [Measurability, architecture_constitution §Framework-Specific Rules]
- [ ] CHK010 - Is Docker Compose version compatibility (v2) verified? [Clarity, plan.md §Tech Stack]

## Module Boundaries

- [ ] CHK011 - Are module public contracts verified for OpenSIPS (MI interface), RTPengine (control socket), and OCP (HTTP/healthcheck)? [Completeness, architecture_constitution §Module Boundaries]
- [ ] CHK012 - Is the "Must Not" column verified for each module (e.g., Asterisk must not do public SIP)? [Coverage, architecture_constitution §Module Boundaries]

## Schema Contracts

- [ ] CHK013 - Is stock OpenSIPS 3.6 schema requirement enforced (ALTER TABLE only for extensions)? [Consistency, architecture_constitution §Contracts]
- [ ] CHK014 - Are PostgreSQL init script idempotency requirements (IF NOT EXISTS, ON CONFLICT) verified? [Measurability, architecture_constitution §Framework-Specific Rules]

## Async & Integration

- [ ] CHK015 - Are async RTPengine operations (offer/answer/delete) verified, not rtpengine_manage? [Completeness, architecture_constitution §Async]
- [ ] CHK016 - Is Prometheus non-blocking scrape contract verified? [Coverage, Gap]
- [ ] CHK017 - Are backup job cron schedules and WAL archiving continuity verified? [Clarity, Gap]

## Evolution Policy

- [ ] CHK018 - Does the plan follow incremental migration policy (add → verify → update → remove)? [Consistency, architecture_constitution §Evolution Policy]
- [ ] CHK019 - Are new Docker services explicitly assigned to defined networks? [Clarity, Gap]
- [ ] CHK020 - Is drift handling classification (P1/P2/P3) applied to any deviations found? [Completeness, architecture_constitution §Refactor and Drift Handling]

## Accepted Deviations

- [ ] CHK021 - Are accepted deviations (cachedb_local, OCP v9 stubs) still valid and documented? [Consistency, architecture_constitution §Accepted Deviations]
- [ ] CHK022 - Is OCP v9 stub migration tracked as technical debt? [Clarity, architecture_constitution §Accepted Deviations]
