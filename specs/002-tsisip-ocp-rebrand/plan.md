# Implementation Plan: TSiSIP Control Panel — Full OCP v9.3.6 Parity

## Summary

This plan implements the feature described in the companion spec.md. It covers infrastructure changes, application code, validation gates, and deployment steps required to deliver the capability in a Docker-first, PostgreSQL-only TSiSIP environment.

## Technical Context

**Language/Version**: Bash, Docker, Docker Compose, Python 3 (for tests), PHP 8.2 (for OCP features), OpenSIPS 3.6 LTS config
**Primary Dependencies**: OpenSIPS 3.6 LTS, PostgreSQL 16, Docker Engine + Compose V2
**Testing**: pytest integration tests, shell-based health probes, PHP syntax validation
**Target Platform**: Docker containers (local dev + VPS production)
**Project Type**: Infrastructure / DevSecOps / SIP edge proxy

## Project Structure

```
specs/002-tsisip-ocp-rebrand/
├── spec.md              # Feature specification
├── plan.md              # This implementation plan
├── tasks.md             # Actionable task breakdown
└── checklists/          # Quality checklists (if present)
```



## Overview

This plan translates the feature specification into an executable implementation roadmap for achieving **100% functional parity** with the official OpenSIPS Control Panel v9.3.6.

---

## Architecture & Stack Choices

### Container Platform
- **Docker Engine** with Docker Compose V2. The OCP v9 runtime is delivered as a TSiSIP-owned Docker image.
- The OCP container attaches to `sip_internal` and `db_internal` networks.
- External access via reverse proxy at path `/TSiSIP/` and `/TSiSIP/wiki/`.

### Base Images
- **OCP v9**: Built from `php:8.2-apache-bookworm` with OCP v9 source, TSiSIP theme layer, and Apache mod_rewrite enabled.
- **PostgreSQL**: Inherited from existing TSiSIP infrastructure.

### Frontend Stack
| Component | Purpose |
|---|---|
| CSS Custom Properties | Palette/theming tokens |
| Inline SVG | Logo, nav icons, status badges |
| D3.js v7 | Interactive SIP metric charts |
| jQuery 3.x | OCP native interactions |
| GNU gettext | i18n localization (EN/ES/PT) |

---

## Implementation Phases

### Phase 0: Foundation & Schema
**Deliverables**:
1. `db/init/04-ocp-parity-schema.sql` — PostgreSQL DDL for all missing OCP v9.3.6 tables.
2. Update `docker/ocp/Dockerfile` to copy new schema files.
3. Rebuild OCP image and verify schema initialization.
4. Update `web/common/role-nav.php` with complete navigation structure.

### Phase 1: Core Infrastructure (High Priority)
**Modules**: Config Table, Dynamic Routing, Sockets Management, TViewer.

### Phase 2: Subscriber Management Completeness (High Priority)
**Modules**: Aliases, Groups, UAC Registrant.

### Phase 3: Operations & Observability (Medium Priority)
**Modules**: Clusterer, SIPtrace, Status Report, Load Balancer.

### Phase 4: Media & Gateway (Low Priority)
**Modules**: RTPProxy, SMPP Gateway, Call Center, Keepalived, Monit.

### Phase 5: Integration & Polish
**Deliverables**:
1. Complete navigation with all 32 modules.
2. Wiki documentation for each new module.
3. Rebuild and deploy OCP Docker image.
4. End-to-end routing verification.

---

## Database Migration Strategy

All new tables are created in `db/init/04-ocp-parity-schema.sql`. The migration:
1. Checks for table existence before CREATE TABLE (idempotent).
2. Follows OpenSIPS 3.6 stock schema exactly.
3. Adds indexes on all foreign keys and frequently filtered columns.
4. Includes COMMENT ON for PostgreSQL documentation.

---

## Rollback Strategy

1. Database: Migrations are idempotent. Rollback via DROP TABLE scripts.
2. Code: New module files are additive. Removing *.php files restores previous state.
3. Navigation: role-nav.php is version-controlled.
4. Container: Rollback to previous Docker image tag.
5. Nginx: Config backups exist on the VPS.
