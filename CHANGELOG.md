# TSiSIP Changelog

All notable changes to the TSiSIP project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — 2026-05-19

### Added
- feat(wiki): Professional Premium Wiki with role-based navigation and audience maps
- feat(wiki): Dentist Clinical Operator Guide for endpoint verification and call quality
- feat(wiki): Front-Desk Assistant Operator Guide for daily health checks and trunk verification
- feat(ocp): Wiki renderer with markdown parser and table of contents
- feat(dashboard): Role-aware landing page with role navigation
- feat(routing): multi-tenant header routing and tenant isolation (Feature 002)
- feat(webrtc): WebSocket/WebRTC transport support (Feature 003)
- feat(cdr): CDR/billing foundation with acc module (Feature 001)
- feat(asterisk): PBX backend integration and end-to-end SIP flow (Feature 007)
- feat(anomaly): anomaly detection integration with Alertmanager (Feature 008)
- feat(ci): CI/CD pipeline with speckit scans (Feature 005)
- feat(deploy): VPS-lite production profile with OpenSIPS TLS bootstrap
- docs(deploy): VPS Deploy Readiness Checklist
- docs(reports): consolidated speckit scan remediation summary
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
- fix(deploy): production readiness with OpenSIPS config hardening and VPS deploy pipeline
- fix(compose): RTPengine NG control protocol switched to TCP over Docker network
- fix(vps): TLS 5061/tcp enabled with self-signed certificates
- fix(compose): reduced VPS RTP port range to 10000-10999 to avoid Docker iptables hang
- fix(speckit): resolved cross-spec consistency issues (C1-C4, H1, H4-H7)
- `web/common/header.php`: integrated TSiSIP asset manifest loader, logo, meta tags
- `web/css/main.css`: OCP base styles compatible with TSiSIP override layer
- `AGENTS.md`: added Feature 002 build/test commands
- `.gitignore`: added Node.js, PHP, and generated asset exclusions

### Fixed
- fix(healthchecks): corrected PostgreSQL, OpenSIPS, and RTPengine health checks
- fix(secrets): trim trailing newlines from secret files; OCP healthcheck uses curl
- fix(entrypoint): use awk instead of tr to read Docker secrets
- fix(backup): resolved speckit-analyze issues for Feature 005
- fix(opensips): corrected tls_mgm 3.6 syntax for certificates
- fix(rtpengine): corrected healthcheck COPY path for root build context
- fix(brownfield): remediated speckit scan findings (image tags, htable removal, memory limits, shm_size)
- fix(memorylint): PostgreSQL memory tuning (shared_buffers, work_mem, max_connections) and OpenSIPS diagnostics (memdump, memlog)
- fix(version-guard): pinned all base images to SHA256 digests and compose image tags to `${TSISIP_IMAGE_TAG:-latest}`

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

## [0.6.0] - 2026-05-17 - Feature 006: SIP-Layer Rate Limiting & DDoS Protection

### Added
- OpenSIPS pike module for per-IP request throttling (50 req/2s)
- cachedb_local module for auth failure counters, ban lists, trunk whitelist
- Rate limiting routes: CHECK_BAN_LIST, RATE_LIMIT, AUTH_FAILURE_TRACK, AUTH_SUCCESS_RESET
- TCP connection limits: tcp_max_connections=4096, tcp_connection_lifetime=300
- Anomaly detection sidecar (Python) with statistical baseline
- Prometheus metrics: tsisip_current_rps, tsisip_baseline_mean_rps, tsisip_anomaly_z_score
- Integration tests: tests/integration/test_rate_limiting.py
- Docker compose integration com anomaly-detector service

### Security
- Per-source IP throttling com silent drop (UDP) ou 429 (TCP/TLS)
- Auth failure tracking: 10 falhas/60s = 403 Forbidden + 5min ban
- Dynamic ban lists com TTL automático via cachedb_local
- Trunk whitelist para NATed enterprise traffic

### Changed
- OpenSIPS Dockerfile: adicionados módulos pike e cachedb_local
- opensips.cfg.tpl: rotas de rate limiting no início do fluxo principal

## [0.8.0] - 2026-05-17 - Feature 008: DevSecOps Deployment Automation

### Added
- playbook-hardening.yml: UFW, fail2ban, unattended-upgrades, tsisip-deploy user
- validate.sh: Deployment validation script (12 checks)
- SPoF falsification tests: test-spof-01-env-missing, test-spof-03-token-leak, test-spof-04-ssh-perms
- Nginx hardening: HSTS, CSP, Permissions-Policy, OCSP stapling, nginx_status
- Makefile targets: hardening, validate, test-spof
- Secret scope separation documentation
- --check-only and --dry-run modes para scripts
- Pre-flight checks no Ansible: disk space, Docker daemon, registry connectivity

### Security
- UFW firewall rules para SIP (5060), RTP (10000-20000), HTTPS (443)
- fail2ban para SSH brute-force protection
- unattended-upgrades para security patches automáticos
- tsisip-deploy user com sudo limitado a Docker commands
- Nginx rate limiting: 30r/m, burst=10, connection limit=10

### Changed
- discover-and-secrets.sh: validação SSH Ed25519, permissões, --check-only
- github-init-repo.sh: --dry-run, validação token, verificação settings
- playbook-deploy.yml: pre-flight checks, docker compose validation, no_log
- nginx config: upstream backend, keepalive, security headers, error pages
- deploy/README.md: documentação completa com troubleshooting

## [0.7.0] - 2026-05-17 - Feature 007: TLS/SRTP Encryption

### Added
- OpenSIPS tls_mgm e proto_tls modules para TLS 1.3
- TLS listener na porta 5061/tcp
- CA tool container (Alpine 3.19 + OpenSSL)
- ca-init.sh: Root CA e Intermediate CA (RSA 4096, SHA-256)
- cert-gen.sh: Geração de server e client certificates
- cert-rotate.sh: Rotação de certificados com backup/rollback
- scripts/tls-reload.sh: Zero-downtime TLS reload via MI
- Docker secrets para ca.crt, server.crt, server.key, crl.pem
- Testes de integração test_tls_srtp.py
- Documentação em docs/features/007/

### Security
- TLS 1.3 preferred, TLS 1.2 minimum
- Cipher suites: ECDHE + AES-256-GCM
- Client certificate verification para trunks
- CRL checking enabled
- ca.key NUNCA montado (offline storage)
- SRTP negotiation via RTPengine SDP rewriting

### Changed
- Dockerfile OpenSIPS: adicionados módulos tls_mgm e proto_tls
- docker-compose.yml: porta 5061/tcp, secrets TLS
- opensips.cfg.tpl: TLS profile, listener 5061
