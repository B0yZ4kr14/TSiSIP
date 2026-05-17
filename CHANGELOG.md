# TSiSIP Changelog

All notable changes to the TSiSIP project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Feature 002: TSiSIP OpenSIPS Control Panel Rebranding & Modernization
  - TSiSIP design system with metallic blue palette (`#1A3A5C` anchor)
  - CSS Custom Properties-based theme engine (`web/tsisip/css/tsisip-variables.css`)
  - Asset pipeline with content-hash filenames and `asset-manifest.json`
  - Responsive logo system (full/compact swap at 768px)
  - Role-aware information density via `[data-tsisip-role]` CSS selectors
  - D3.js v7 chart module with isolated ES module scope (`tsisip-charts.js`)
  - Dispatcher load chart and RTPengine session chart stubs
  - Trilingual i18n (EN/ES/PT) via GNU gettext `.po`/`.mo` files
  - Accessibility audit suite (WCAG 2.1 AA checks)
  - Docker image for OCP v9 (`docker/ocp/Dockerfile`)
  - Orchestrated build script (`scripts/build-ocp-theme.sh`)
  - Rollback script (`scripts/rollback-ocp-theme.sh`)
  - DESIGN.md documenting the complete visual system
- GitHub Actions CI readiness (Lighthouse budget, visual regression configs)

### Changed
- `web/common/header.php`: integrated TSiSIP asset manifest loader, logo, meta tags
- `web/css/main.css`: OCP base styles compatible with TSiSIP override layer
- `AGENTS.md`: added Feature 002 build/test commands
- `.gitignore`: added Node.js, PHP, and generated asset exclusions

### Security
- All TSiSIP assets are self-hosted (no CDN dependencies)
- Asset filenames include content hashes for cache-busting
- D3.js loaded on-demand only in chart views (not globally)
- Zero `eval()` usage in TSiSIP JavaScript modules
- No inline event handlers in injected SVGs

## [0.1.0] -- 2026-05-17

### Added
- Feature 001: OpenSIPS Docker Edge Proxy Foundation
  - OpenSIPS 3.6 LTS Docker image (`Dockerfile`)
  - RTPengine container image (`docker/rtpengine/Dockerfile`)
  - Asterisk PBX container image (`docker/asterisk/Dockerfile`)
  - Docker Compose three-network topology (`sip_edge`, `sip_internal`, `db_internal`)
  - PostgreSQL schema initialization (stock + TSiSIP extensions + seed data)
  - OpenSIPS configuration template with auth, routing, topology hiding
  - Container entrypoint with secret loading and `envsubst`
  - Runtime SIP validation: OPTIONS 200 OK, INVITE 407 Proxy-Auth
  - `.env.example` and secrets directory structure

### Security
- SIP Digest credentials stored as HA1 hashes only
- PostgreSQL and Asterisk have no host-published ports
- RTPengine control socket bound to internal network only
- Capabilities dropped to minimum required set

## [0.3.0] - 2026-05-17 - Feature 003: Observability Platform

### Added
- Prometheus server (v2.51) com TSDB retention de 30d/10GB
- Alertmanager (v0.27) com routing por severidade e webhooks
- 5 alert rules: dispatcher degradation, auth spike, RTP utilization, PostgreSQL slow queries, disk full
- OpenSIPS metric exporter sidecar (Python) com cache TTL 10s
- Grafana (v10.4) com 3 dashboards: Dispatcher Health, Capacity Planning, Deployment Validation
- i18n para dashboards (EN/ES/PT)
- Testes de integração pytest para stack de observabilidade
- Serviços integrados ao docker-compose.yml

### Changed
- docker-compose.yml atualizado com prometheus, alertmanager, grafana, opensips-exporter

### Technical Details
- Prometheus usa entrypoint com envsubst para renderizar config template
- Exporter faz polling da MI interface do OpenSIPS e expõe métricas no formato Prometheus
- Dashboards JSON são provisionados automaticamente via Grafana provisioning

## [0.4.0] - 2026-05-17 - Feature 004: Health Checks & Auto-Healing

### Added
- Health check scripts para OpenSIPS, PostgreSQL, RTPengine, Asterisk
- Docker HEALTHCHECK em todos os containers com staggered start periods
- Restart policies: unless-stopped (críticos), on-failure (suporte)
- Circuit breaker via dispatcher probing (5 failures/30s, half-open 60s)
- Graceful degradation: 488 (RTPengine down), 480 (PostgreSQL down)
- Health metrics no OpenSIPS exporter
- Grafana Health Status dashboard
- Testes de integração para restart policy, circuit breaker, graceful degradation

### Changed
- docker-compose.yml atualizado com health checks e restart policies
- Dockerfile do OpenSIPS com health check script
- Dockerfile customizado para PostgreSQL, RTPengine, Asterisk
- opensips.cfg.tpl com circuit breaker e graceful degradation routes

### Security
- Container health visibility para detecção precoce de falhas
- Circuit breaker previne routing para backends falhos

## [0.5.0] - 2026-05-17 - Feature 005: PostgreSQL Backup & Restore

### Added
- Backup container (postgres:16 base) com pg_dump, rclone, openssl, cron
- backup.sh: logical backup com pg_dump -Fc -Z9, --lock-wait-timeout=5000
- encrypt.sh: AES-256-CBC com PBKDF2 via OpenSSL
- wal-archive.sh: compressão e criptografia de WAL segments
- purge.sh: retenção 30d backups, 37d WAL (7d beyond oldest backup)
- validate.sh: restore validation com row counts
- replicate.sh: rclone sync com bandwidth limit 50M
- rclone.conf.tpl: S3-compatible storage config
- entrypoint.sh: cron scheduling (backup 02:00, purge 03:00, validate 04:00, replicate hourly)
- Backup service no docker-compose.yml
- PostgreSQL WAL archiving configurado (archive_mode=on, archive_timeout=300)
- Teste de integração test_backup_restore.py

### Security
- Backups criptografados com AES-256-CBC
- Encryption key via Docker secrets
- ca.key NÃO montado no container (offline)
