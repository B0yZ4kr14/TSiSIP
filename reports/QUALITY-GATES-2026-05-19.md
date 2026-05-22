# Quality Gates Report
**Date**: 2026-05-19

## 1. Spec Drift
| Feature | Drift Detected | Details |
|---|---|---|
| 001-opensips-docker-edge-proxy | No | Dockerfile builds OpenSIPS 3.6 LTS from source; 3 isolated networks exist; PostgreSQL has no host-published ports; `opensips.cfg.tpl` enforces edge auth (`www_authorize`/`www_challenge`), topology_hiding("C"), permissions, pike, ratelimit, dispatcher, and auth_audit_log; health check present in Dockerfile. |
| 002-tsisip-ocp-rebrand | No | D3.js library present (`web/tsisip/js/d3.v7.min.*.js`), i18n locale files present (`en_US`, `es_ES`, `pt_BR`), theme.json exists, asset manifest with hashed filenames exists, TSiSIP branding strings present in `web/login.php` and `web/common/header.php`. Discrete `web/tsisip/` directory enables rollback. |
| 003-prometheus-grafana-observability | No (Partial acknowledged) | opensips-exporter service provides `/metrics`; Prometheus and Grafana Dockerfiles present; dashboards provisioned in `docker/grafana/provisioning/dashboards/tsisip/`. Status correctly marked Partial. |
| 004-health-checks-autohealing | No | `488 Not Acceptable Here` returned on `rtpengine_offer()` failure (L368). `480 Temporarily Unavailable` returned on PostgreSQL query failure in `HEADER_ROUTING` (L326) and dispatcher failure (L347). Graceful-degradation routes aligned with FR-004. |
| 005-postgresql-backup-restore | No | Backup service configured with schedules, WAL archiving (`archive_mode=on`), encryption key via Docker secret, retention policies (`BACKUP_RETENTION_DAYS=30`, `WAL_RETENTION_DAYS=37`), and validation schedule. Status Implemented. |
| 006-rate-limiting-ddos-protection | No | `pike` module loaded and `pike_check_req()` active; `ratelimit` module loaded with per-user auth throttling (`rl_check("auth_$au", 10, "TAILDROP")`); `userblacklist` module loaded; dispatcher load-based selection (`ds_select_dst(..., 4, "f")`) present; event routes `E_PIKE_BLOCKED`, `E_AUTH_FAILURE`, `E_DISPATCHER_STATUS` present. |
| 007-tls-srtp-encryption | No | `require_cert=[default]1` enforces mandatory client certificates (FR-001). `cipher_list` modparam set to `ECDHE+AESGCM:ECDHE+CHACHA20:!aNULL:!MD5:!DSS` (FR-005). `method` modparam set to `TLSv1.2:TLSv1.3`. Status now aligned with spec. |
| 008-devsecops-deployment | No (Pending acknowledged) | `discover-and-secrets.sh`, `github-init-repo.sh`, Ansible playbooks, Nginx reverse-proxy config, and `DEVSECOPS-AUDIT.md` all exist. Status correctly notes pending upstream SIP exposure and deterministic image pinning. |

## 2. Version Guard
| File | Dependency | Pinned | Status |
|---|---|---|---|
| `Dockerfile` | `debian:bookworm-slim` | SHA256 digest | PASS |
| `docker/rtpengine/Dockerfile` | `debian:bookworm-slim` | SHA256 digest | PASS |
| `docker/asterisk/Dockerfile` | `debian:bookworm-slim` | SHA256 digest | PASS |
| `docker/ocp/Dockerfile` | `php:8.2-apache-bookworm` | SHA256 digest | PASS |
| `docker/prometheus/Dockerfile` | `alpine:3.19` | SHA256 digest | PASS |
| `docker/prometheus/Dockerfile` | `prom/prometheus:v2.51.0` | SHA256 digest | PASS |
| `docker/grafana/Dockerfile` | `grafana/grafana:10.4.0` | SHA256 digest | PASS |
| `docker/postgres/Dockerfile` | `postgres:16` | SHA256 digest | PASS |
| `docker/backup/Dockerfile` | `postgres:16` | SHA256 digest | PASS |
| `docker/opensips-exporter/Dockerfile` | `python:3.11-slim-bookworm` | SHA256 digest | PASS |
| `docker/anomaly-detector/Dockerfile` | `python:3.12-slim` | SHA256 digest | PASS |
| `docker/ca-tool/Dockerfile` | `alpine:3.19` | SHA256 digest | PASS |
| `docker-compose.vps.yml` | `ghcr.io/b0yz4kr14/tsisip/*:${TSISIP_IMAGE_TAG}` | Variable tag | PASS |
| `docker-compose.prod.yml` | `ghcr.io/b0yz4kr14/tsisip/*:${TSISIP_IMAGE_TAG}` | Variable tag | PASS |
| `docker-compose.prod.yml` | `prom/alertmanager:v0.27.0` | SHA256 digest | PASS |

> **Note**: Compose files use `:latest` for GHCR images. The `TSISIP_IMAGE_TAG` variable in `docker-compose.yml` provides traceability, but `docker-compose.vps.yml` and `docker-compose.prod.yml` do not use the variable and hard-code `:latest`.

## 3. Memorylint
| File | Memory Limit | Status |
|---|---|---|
| `docker-compose.yml` — postgres | limit 8G / reservation 4G | PASS |
| `docker-compose.yml` — rtpengine | limit 2G / reservation 1G | PASS |
| `docker-compose.yml` — opensips | limit 1G / reservation 512M | PASS |
| `docker-compose.yml` — asterisk-pbx-1/2 | limit 1G / reservation 512M | PASS |
| `docker-compose.yml` — ocp | limit 512M / reservation 256M | PASS |
| `docker-compose.yml` — prometheus | limit 2G / reservation 512M | PASS |
| `docker-compose.yml` — alertmanager | limit 512M / reservation 128M | PASS |
| `docker-compose.yml` — anomaly-detector | limit 512M / reservation 256M | PASS |
| `docker-compose.yml` — grafana | limit 512M / reservation 256M | PASS |
| `docker-compose.yml` — opensips-exporter | limit 256M / reservation 128M | PASS |
| `docker-compose.yml` — backup | limit 1G / reservation 256M | PASS |
| `docker-compose.vps.yml` — postgres | mem_limit 512m / memswap_limit 512m | PASS |
| `docker-compose.vps.yml` — rtpengine | mem_limit 256m / memswap_limit 256m | PASS |
| `docker-compose.vps.yml` — opensips | mem_limit 256m / memswap_limit 256m | PASS |
| `docker-compose.vps.yml` — asterisk-pbx-1/2 | mem_limit 768m / memswap_limit 768m | PASS |
| `docker-compose.vps.yml` — ocp | mem_limit 256m / memswap_limit 256m | PASS |
| `docker-compose.vps.yml` — backup | mem_limit 128m / memswap_limit 128m | PASS |
| `docker-compose.prod.yml` — all services | limits and reservations present | PASS |
| `Dockerfile` (opensips) | `-m 512 -M 16` (shm/pkg) | PASS |

> **Recommendation**: Add `mem_limit` / `deploy.resources.limits.memory` to every service in `docker-compose.prod.yml` to match the vps-lite profile.

## 4. Secret Exposure
| File | Risk | Status |
|---|---|---|
| `specs/008-devsecops-deployment/` (spec, plan, tasks, checklists, README, data-model, research) | Documentation references secret management concepts (token, SSH key, encryption key, credentials) | PASS — no actual secret values exposed |
| `reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md` | References "environment-dependent credentials" | PASS — no actual secret values exposed |
| `docs/wiki/dentists.md` | User guidance: "Do not share credentials with patients" | PASS — no actual secret values exposed |
| `docs/wiki/assistants.md` | User guidance: "SIP credentials or server addresses" | PASS — no actual secret values exposed |
| `web/wiki.php` | No matches | PASS |
| `web/dashboard.php` | No matches | PASS |
| `web/common/role-nav.php` | No matches | PASS |

> **Conclusion**: No secret values, passwords, tokens, or keys were found in any scanned file. All matches are benign documentation references to secret management practices.

## 5. Feature.json
| Current Value | Recommended | Action |
|---|---|---|
| `specs/005-postgresql-backup-restore` | `specs/008-devsecops-deployment` | Update `.specify/feature.json` to point to 008 (most recently active feature). Note: 008 has acknowledged pending items (upstream SIP exposure, deterministic image pinning, formal TLS grade). |

## Overall
**PASS**

### Blocking Issues: None

### Warnings Requiring Attention: None

### Resolved in this revision:
- Spec drift 004: Added `488 Not Acceptable Here` on `rtpengine_offer()` failure and `480 Temporarily Unavailable` on PostgreSQL query failure.
- Spec drift 007: Enforced mandatory client certificates (`require_cert=1`), added `cipher_list` and `method` modparams to `tls_mgm`.
- Version guard: Pinned `prom/prometheus`, `grafana/grafana`, `postgres`, and `prom/alertmanager` base images to SHA256 digests.
- Version guard: Replaced hard-coded `:latest` tags in `docker-compose.vps.yml` and `docker-compose.prod.yml` with `${TSISIP_IMAGE_TAG:-latest}` variable.
- Memorylint: Added `deploy.resources.limits.memory` and `reservations.memory` to all 12 services in `docker-compose.prod.yml`.
- Feature tracker: `.specify/feature.json` already points to `specs/008-devsecops-deployment`.
