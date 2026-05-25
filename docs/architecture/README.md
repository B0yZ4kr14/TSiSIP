# Architecture Decision Records (ADR) — TSiSIP

This directory contains formal Architecture Decision Records (ADRs) for TSiSIP. Decisions that affect multiple features or define project-wide standards are recorded here. Feature-scoped decisions remain in their respective `specs/NNN-feature-name/` directories.

## Index

| ADR | Title | Status | Date | Scope |
|-----|-------|--------|------|-------|
| [ADR-001](ADR-001-vps-lite-profile.md) | VPS Lite Deployment Profile | Accepted | 2026-05-21 | Infrastructure |
| [ADR-023](023-adr-subscriber-proxy.md) | Subscriber CRUD Proxy Layer | Accepted | 2026-05-24 | Security / Auth |

## Architecture References

| Document | Description | Last Updated |
|----------|-------------|--------------|
| [OCP Gap Analysis](OCP-GAP-ANALYSIS.md) | Gap analysis between TSiSIP OCP and Official OCP v9 | 2026-05-25 |

## Wiki / Control Panel Separation

The TSiSIP Wiki has been separated from the Control Panel as an independent sub-application:
- **Control Panel**: `https://tsiapp.io/TSiSIP` — OCP v9 with TSiSIP branding
- **Wiki**: `https://tsiapp.io/TSiSIP/wiki` — Markdown-based documentation with TSiSIP branding
- Implementation: `web/wiki/` directory with isolated header/footer, nginx `location /TSiSIP/wiki/`

## Feature-Scoped Architecture Decisions

| Feature | Decision | Location |
|---------|----------|----------|
| 020 | AD-1: Database-Driven Dialplan | `specs/020-ocp-critical-tool-gap-closure/spec.md` |
| 020 | AD-2: MI Command Whitelist | `specs/020-ocp-critical-tool-gap-closure/spec.md` |
| 020 | AD-3: Read-Only Dialog Viewer | `specs/020-ocp-critical-tool-gap-closure/spec.md` |
| 023 | AD-1: OpenSIPS MI vs REST API | `specs/023-subscriber-crud-refactor/spec.md` |
| 023 | AD-2: HA1 Generation Stays in OCP | `specs/023-subscriber-crud-refactor/spec.md` |
| 023 | AD-3: Proxy Auth via Internal Network + Shared Secret | `specs/023-subscriber-crud-refactor/spec.md` |
| 024 | AD-024-1: Dynamic IP Discovery via docker network inspect | `specs/024-brownfield-remediation/spec.md` |
| 024 | AD-024-2: env-example Placeholder Values | `specs/024-brownfield-remediation/spec.md` |
| 024 | AD-024-3: Dockerfile HEALTHCHECK Conventions | `specs/024-brownfield-remediation/spec.md` |

## When to Elevate a Decision to This Directory

Promote a feature-scoped ADR to this directory when:
- It affects more than one feature or service
- It changes a constitution gate or baseline architecture rule
- It introduces a new cross-cutting concern (security, networking, data model)

## Format

ADRs in this directory follow the [ADR template](https://adr.github.io/) with TSiSIP-specific additions:
- **Constitution Impact**: Does this decision require a constitution update?
- **Rollback Plan**: How to revert if the decision proves wrong
- **Evidence**: Links to spec, implementation, and validation artifacts

---

*Last updated: 2026-05-25*
