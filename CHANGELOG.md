# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Feature 031: OCP REST API v1
- Public status endpoint (`GET /api/v1/status`) with CORS
- Authenticated endpoints: `/api/v1/metrics`, `/api/v1/users`, `/api/v1/audit`
- Bearer token authentication with bcrypt-hashed API keys (`ocp_api_keys`)
- Rate limiting: 100 req/minute sliding window per key
- Admin pages: `api-keys.php`, `api-docs.php`
- Apache rewrite rules (`.htaccess`) for clean URI routing

#### Feature 030: OCP User Management & RBAC
- PostgreSQL schema extensions: `ocp_password_history`, `ocp_user_sessions`
- Password policy enforcement (history, complexity, expiration)
- Admin pages: `users.php`, `user-edit.php`, `user-delete.php`
- Self-service profile page (`profile.php`) with password change
- Session invalidation on password change
- Role-based navigation (`role-nav.php`) with 6 roles: admin, devops, dentist, assistant, user, readonly

#### Feature 029: OCP Frontend — 100% OpenSIPS 3.6 MI Parity
- Generic MI action handler (`web/common/mi-action.php`) with 67 whitelisted commands
- `mi-actions.js` helper with `attachReload`, `attachToggle`, `attachRowAction`
- MI actions on 12 existing pages: reload, terminate, toggle, refresh controls
- 14 new module pages: Memory Status, Pike Monitor, Rate Limit, USRLoc Live,
  Hash Tables, NAT Helper, TCP Connections, Topology Hiding, Processes,
  Blacklists, Version, Timers, Presence, AVP Inspector
- MI command executor expanded from 6 to 46+ commands with categories and search
- Navigation updated with Runtime, Security, NAT & Presence, Advanced groups
- Dashboard quick links to new runtime and security pages
- System Events page (`web/system-events.php`) for audit log browsing with filters
- Human-friendly MI error banners (`web/common/mi-error-helper.php`)
- 4 new integration test suites validating MI actions, new pages, whitelist, and smoke

#### Feature 018: Global Requirement ID Migration
- Feature-scoped FR-NNN-XXX identifiers across all 24 specs
- Validation scripts (`scripts/validate-fr-ids.py`, `scripts/migrate-fr-ids.py`)

#### Feature 019: Spec Kit Memory Hub Integration
- Automated memory synthesis per feature cycle
- Blueprint generation and spec validation gates

#### Feature 020: OCP Critical Tool Gap Closure
- Dialog viewer (read-only), MI command runner (whitelist), Statistics monitor (D3.js)
- Dialplan manager, Domains manager, TLS management UI
- Security assessment and threat model documentation

#### Feature 021: Brownfield Security & Production Hardening
- CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy headers
- Session hardening (regenerate_id, HttpOnly, SameSite=Strict)

#### Feature 022: VPS Go-Live Stabilization
- Production validation, port audits, network segmentation tests
- Unified nginx reverse proxy with Cloudflare Origin CA

#### Feature 023: Subscriber CRUD Refactor
- Admin API proxy layer for subscriber mutations
- HA1 generation remains in OCP; INSERT/UPDATE/DELETE delegated to proxy
- ARCH-PRE-001 resolution

#### Feature 024: Brownfield Remediation
- SHA-pinned Docker base images (supply-chain determinism)
- Dynamic IP discovery via `docker network inspect` in deploy scripts
- Dockerfile HEALTHCHECK instructions for all services
- Complete `.env.example` parity with `docker-compose.vps.yml`

### Changed

- `.editorconfig` added for consistent formatting
- `CONTRIBUTING.md` added with workflow, gates, and conventions
- `docs/architecture/README.md` added as ADR index
- `certbot-exporter` now monitors multiple certificates (Origin CA + certbot)
- `Makefile` gained `brownfield` target for hygiene scans
- `prometheus.yml.tpl` updated with `postgres-exporter` and `node-exporter` jobs
- `docker-compose.yml` gained `postgres-exporter` and `node-exporter` services

## [0.7.0] - 2026-05-20

### Added

#### Feature 015: Automated TLS Certificate Rotation
- Containerized certbot service for Let's Encrypt ACME v2 HTTP-01 challenges
- Tailscale internal certificate service for tailnet endpoints
- Zero-downtime OpenSIPS TLS reload via MI HTTP (`tls_reload`)
- Atomic certificate deploy with pre-flight validation (`deploy-hook.sh`)
- Prometheus certbot-exporter with 6 Alertmanager alert rules (30d/14d/7d/1d/expired/renewal-failed)
- Integration tests (`tests/integration/test-tls-rotation.sh`)

#### Feature 016: OCP Audit Log & Compliance Dashboard
- Immutable PostgreSQL audit log (`ocp_audit_log`) with SHA-256 hash chain
- Compliance dashboard (`web/audit-log.php`) with filters, pagination, JSON details
- CSV/JSON export endpoint (`web/audit-export.php`)
- Automated retention purge via cron (`docker/ocp/cron/audit-retention.sh`)
- PHP audit library (`web/common/audit.php`) instrumenting login, logout, password changes, subscriber/dispatcher CRUD
- Integration tests (`tests/integration/test-ocp-audit.sh`, `test-audit-dashboard.sh`)

#### Feature 017: SIP Trunk Provider Integration
- PostgreSQL schema for trunk providers, DID mappings, and registrations
- OpenSIPS outbound trunk routing with priority selection and failover (`route[TRUNK_ROUTING]`)
- Inbound DID routing with trusted-IP bypass (`route[INBOUND_DID_ROUTING]`)
- Per-trunk CPS rate limiting and health monitoring via dispatcher OPTIONS probes
- OCP admin pages: `trunk-providers.php`, `trunk-dids.php`, `trunk-status.php`
- Grafana dashboard (`TSiSIP — SIP Trunk Providers`)
- 5 Python integration tests (`tests/integration/test_sip_trunk_*.py`)

#### DevSecOps Hardening (Feature 008)
- Trivy CVE scanning in CI/CD pipeline (`.github/workflows/deploy.yml`)
- Docker hardening: `cap_drop: [ALL]` and `security_opt: ["no-new-privileges:true"]` on all services
- Backup S3 credentials migrated from plain env vars to Docker secrets
- Removed `latest` tag fallback from `docker-compose.yml` (strict `${TSISIP_IMAGE_TAG:?must be set}`)
- Non-root `USER` directives in `opensips-exporter` and `anomaly-detector` Dockerfiles
- Ansible `no_log` coverage for secret-handling tasks

#### Memorylint Remediation
- M1: OpenSIPS pkg_mem_size increased from 16MB to 32MB
- M3: VPS backup `mem_limit` increased from 128m to 256m
- M4: Explicit Prometheus TSDB retention (30d / 10GB)
- M5: Explicit OpenSIPS `shm_mem_size = 512` in config template

### Changed
- `AGENTS.md` updated with Feature 015/B/C test commands and validation procedures
- `docs/TSiSIP-OPERATOR-RUNBOOK.md` expanded with operator sections for TLS rotation, audit compliance, and trunk management
- Cross-project consistency: `constitution.md`, `brownfield-scan-report.md`, `memorylint-report.md`, `remediation-summary.md`, and `security-compliance.md` synchronized with ground truth

### Fixed
- **Audit log hash chain integrity**: trigger now uses `host(ip_address)` instead of `ip_address::TEXT` to avoid CIDR mask (`/32`) mismatch between PostgreSQL canonicalization and PHP read-back
- **Immutability trigger**: `fn_ocp_audit_log_immutable()` returns `NEW` for UPDATE as `tsisip_retention` (was `OLD`, which silently cancelled updates)
- **Test suite**: `((PASS++))` arithmetic expansion in `test-ocp-all.sh` returned exit code 1 under `set -e` when `PASS=0` — replaced with POSIX-safe `$((PASS + 1))`
- `miHttpAvailable()` now uses POST instead of GET to avoid OpenSIPS MI HTTP error log spam
- `.dockerignore` blocking `docs/wiki/` broke OCP Docker build — fixed by removing `docs/` exclusion
- Brownfield residual findings B14-B16 (backup encryption, healthchecks, CI `latest` tag documentation)
- Speckit-analyze issues across all 014 specs (placeholders, port numbers, schema references, plan corruption)
- `scripts/ci-scan.sh` robustness: handles missing PHP, missing env vars, and non-running compose stacks gracefully

