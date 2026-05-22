# Architecture Constitution — TSiSIP

> Architecture enforcement document for Architecture Guard.

## Architecture Style

- **Style**: Microservices (Docker Compose orchestration)
- **Primary stack**: OpenSIPS 3.6 LTS + PostgreSQL 16 + RTPengine + Asterisk + PHP 8.2 Apache
- **Preset guidance**: None (infrastructure platform, not application framework)

## Layer Boundaries

| Layer | Owns | May Depend On | Must Not Depend On |
| --- | --- | --- | --- |
| Edge (SIP) | OpenSIPS signaling, auth, routing, topology hiding | PostgreSQL (auth_db), RTPengine (control) | Asterisk internals, direct DB writes |
| Media | RTPengine SDP rewriting, SRTP relay | OpenSIPS (control socket) | SIP signaling logic |
| Database | PostgreSQL schemas, migrations, seed data | Volume persistence | Application logic |
| PBX | Asterisk dialplan, PJSIP, applications | Internal SIP from OpenSIPS | Public SIP directly |
| Control Plane | OCP PHP frontend, monitoring, MI interface | PostgreSQL (read-only ops) | OpenSIPS runtime state |
| Observability | Prometheus, Grafana, Alertmanager, anomaly detector | Metrics endpoints | Business logic |

## Business Logic Placement

- OpenSIPS config (`opensips.cfg.tpl`) owns all SIP signaling logic, auth decisions, and routing rules.
- PostgreSQL owns subscriber data, tenant metadata, dispatcher state, CDR, audit logs.
- Asterisk owns voice/video application logic (IVR, voicemail, conferencing).
- OCP owns administrative UI, wiki rendering, and read-only system status.
- No layer may bypass OpenSIPS to route SIP directly to Asterisk from the public internet.

## Contracts and Validation

- **SIP contracts**: RFC 3261, RFC 8760 (Digest SHA-256/512), RFC 3264 (SDP offer/answer)
- **Database contracts**: Stock OpenSIPS 3.6 schema + TSiSIP extensions (`ALTER TABLE` only)
- **Docker contracts**: Project-owned images only; pinned base image SHA; no `:latest` in production
- **Validation boundary**: All external SIP input is validated via `sanity` replacement (custom header checks), `check_source_address`, and `www_authorize`/`proxy_authorize`.

## Data Access Rules

- OpenSIPS reads subscriber data via `db_postgres` module; writes via `sql_query` for audit logging.
- OCP reads from `ocp_users`, `subscriber`, `dispatcher` via PDO; never writes to `subscriber` directly.
- Backup service reads PostgreSQL via `pg_dump`; writes to S3-compatible storage via rclone.
- Cross-service data access must use defined SQL schemas; no direct filesystem access between containers.

## Async and Integration Rules

- RTPengine operates asynchronously from SIP signaling via `rtpengine_offer()`/`rtpengine_answer()`/`rtpengine_delete()`.
- Prometheus scrapes metrics from OpenSIPS MI interface and opensips-exporter; no blocking calls.
- Alertmanager webhooks are fire-and-forget; anomaly detector runs independently.
- Backup jobs run on cron schedules; WAL archiving is continuous.

## Module Boundaries

| Module | Owns | Public Contracts | Must Not |
| --- | --- | --- | --- |
| OpenSIPS | SIP proxy, auth, routing, topology hiding | `opensips.cfg` (rendered), MI interface | Direct Asterisk management, DB schema changes |
| RTPengine | Media relay, SDP rewriting, SRTP | Control socket (UDP 22222), RTP ports (10000-20000) | SIP signaling, auth decisions |
| PostgreSQL | All persistent data | SQL schema, pg_isready healthcheck | SIP processing, media relay |
| Asterisk | PBX applications | Internal SIP (5060), PJSIP config | Public SIP, direct DB access |
| OCP | Admin UI, wiki, status dashboard | HTTP 80, healthcheck on `/login.php` | SIP processing, DB writes to core tables |
| Backup | Scheduled dumps, replication, validation | Backup cron, S3 sync | Runtime SIP, live DB mutations |

## Framework-Specific Architecture Rules

- OpenSIPS config template (`opensips.cfg.tpl`) must pass `opensips -c` validation after envsubst.
- Dockerfiles must use `sha256:`-pinned base images; `FROM` with `:latest` is rejected.
- Docker Compose services must declare explicit networks; `sip_internal` and `db_internal` must not publish ports.
- PHP OCP must use PDO prepared statements; no raw SQL concatenation.
- PostgreSQL init scripts must be idempotent (`IF NOT EXISTS`, `ON CONFLICT DO NOTHING`).

## Blocking Architecture Violations (P0)

- **P0**: No public endpoint may bypass OpenSIPS auth (trusted gateway whitelist is the only exception, and it requires `check_source_address`).
- **P0**: No container may expose PostgreSQL or Asterisk ports to the host.
- **P0**: `subscriber` table must not store plaintext passwords; only HA1 hashes allowed.
- **P0**: OpenSIPS config must not reference `db_mysql`, `db_sqlite`, or `sanity` module.
- **P0**: RTPengine control socket (`--listen-ng`) must bind to `sip_internal` network only.

## Accepted Architecture Deviations

- `cachedb_local` replaces `htable` (module absent from OpenSIPS 3.6 tree).
- OCP v9 uses stub PHP files rather than full OCP v9 source (acceptable for MVP; migration tracked as technical debt).
- Health checks use `curl`/`wget` inside containers rather than external probes.

## Architecture Evolution Policy

- Repeated drift (e.g., new module additions, network changes) triggers a Constitution Update Proposal via `/speckit.architecture-guard.init`.
- New Docker services require explicit network assignment (`sip_edge`, `sip_internal`, `db_internal`, or `metrics_host`).
- Migration plans must be incremental: add service → verify health → update routing → remove old service.

## Refactor and Drift Handling

- **P1 drift** (e.g., config template inconsistency, missing index) → near-term refactor tasks.
- **P2 drift** (e.g., documentation gaps, non-canonical naming) → tracked as scheduled technical debt.
- **P3 cleanup** (e.g., comment formatting, log message consistency) → opportunistic, must not block feature delivery.

## Cross-References

- Governance principles: `.specify/memory/constitution.md`
- Security standards and incident response: `.specify/memory/security_constitution.md`
