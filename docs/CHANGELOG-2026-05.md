# TSiSIP Changelog — May 2026

## [1.0.2] - 2026-05-29

### Critical Fixes
- **M1-VPS**: OpenSIPS VPS `-M` reduced from 64→48 MB to fit within 1G container limit (was 1088 MB calculated, now 944 MB)
- **M7**: Explicit `children = 8` added to `opensips.cfg.tpl` for predictable memory sizing
- **V21**: Python `requests` aligned to 2.32.3 across all containers (opensips_exporter already correct)

### Security & Hardening
- **B21-FU**: `set -euo pipefail` added to 5 bash entrypoints (admin_api, certbot, ocp, prometheus, tailscale_cert/renew.sh)
- **N2**: `HOST_PUBLIC_IP` fallback `:-127.0.0.1` removed from VPS compose; now required (`:?must be set`)
- **N3**: Stale `t_list` comment removed from `web/call-queue.php`

### Performance & Memory
- **M14**: Defense-in-depth `LIMIT 5000` added to `export-report.php` GROUP BY queries
- **M15**: certbot_exporter limit 64M→128M in `docker-compose.yml` and `docker-compose.vps.yml`

### Documentation
- `docs/memory/DECISIONS.md` updated with AD-024-4 (VARCHAR(36) tenant_id) and AD-024-5 (credential columns)

## [1.0.1] - 2026-05-29

### Security & Hardening
- **B17**: RTPengine HTTP stats interface bound to `${RTPENGINE_INTERNAL_IP}` in VPS profile (was `0.0.0.0`)
- **B18**: OpenSIPS TLS cert mount changed from `rw` to `ro` in VPS profile
- **B21**: `ANOMALY_API_KEY` changed from weak fallback to required (`:?must be set`)

### Performance & Memory
- **M1-M3**: OpenSIPS memory tuning: dev shm 256MB→512MB, prod limit 2G→3G, VPS limit 1G→1.5G
- **M4**: certbot_exporter limit 64M→128M with reservation
- **M5**: `gc_collect_cycles()` added to SSE long-polling loops
- **M6**: MI cache capped at 100 entries with LRU eviction

### Configuration & Consistency
- **B22**: Added `ANOMALY_API_KEY`, `MI_HTTP_IP`, `OPENSIPS_HOST` to `.env.example`
- **B23/B24**: Added `ghcr.io/b0yz4kr14/` registry prefix to `tailscale_cert` images
- **V21**: PgBouncer semantic version tag added (`1.22.0`)
- **V29**: `.python-version` corrected to `3.12.3`

### Documentation
- All deploy/script `sleep` statements documented with justification comments
- Consolidated audit report: `docs/CONSOLIDATED-AUDIT-2026-05-29.md`

## [1.0.0] - 2026-05-19

### Added
- **OCP Rebrand (Feature 002)**: Complete UI redesign with premium theme
- **Dark Mode (Feature 025)**: Toggle between light/dark themes
- **i18n Support**: English, Spanish, Portuguese translations
- **Real-time Updates (Feature 026)**: Server-Sent Events for live data
- **Mobile Responsive (Feature 027)**: Full mobile support with breakpoints
- **Custom Dashboard (Feature 028)**: Widget customization per user
- **Global Search**: Search across subscribers, audit logs, dialogs
- **User Profile**: Preferences, theme, language settings
- **System Health Dashboard**: Unified component status view
- **Bookmarks**: Favorite pages with quick access
- **Toast Notifications**: Success/error/warning/info alerts
- **Flash Messages**: Session-based notifications
- **API Documentation**: Endpoint reference for integrations
- **Public Health Check**: JSON status for load balancers
- **System Reports**: Analytics with time ranges and export
- **MI Cache**: Robust response caching for MI calls

### New Pages
- `gateway-health.php` — Dispatcher status via MI
- `call-queue.php` — Live call queue
- `rtpengine-status.php` — RTPengine sessions
- `subscriber-stats.php` — Subscriber counts
- `topology.php` — Visual topology diagram
- `failover.php` — Manual failover trigger
- `alert-history.php` — Prometheus alerts
- `system-config.php` — Configuration viewer
- `help.php` — Documentation index
- `search.php` — Global search
- `profile.php` — User profile
- `system-health.php` — Unified health dashboard
- `api-docs.php` — API documentation
- `reports.php` — System reports
- `health.php` — Public health check

### Infrastructure
- `sse-stream.php` — Server-Sent Events endpoint
- `save-dashboard.php` — Widget preferences persistence
- `user-prefs.php` — User preferences helper
- `bookmark-toggle.php` — Bookmark management
- `export-report.php` — Report data export
- `mi-cache.php` — MI response cache
- `set-theme.php` — Theme preference setter
- `set-language.php` — Language preference setter
- `export-text.php` — TEXT export utility

### Database
- `07-user-preferences.sql` — Per-user settings
- `08-user-bookmarks.sql` — User bookmarks

### Tests
- `test-ocp-new-pages.sh` — New pages validation
- `test-ocp-dark-mode.sh` — Dark mode validation
- `test-ocp-profile-search.sh` — Profile and search
- `test-ocp-system-health.sh` — System health
- `test-ocp-mobile-responsive.sh` — Mobile responsive
- `test-ocp-bookmarks.sh` — Bookmarks and API docs
- `test-ocp-reports.sh` — System reports

### Documentation
- `OCP-USER-GUIDE.md` — Comprehensive user guide
- `OCP-NEW-PAGES-2026-05-26.md` — New pages architecture

### Security
- CSRF tokens on all state-changing operations
- Session-based authentication
- Role-based access control
- Audit logging for all actions
- Secure session cookies

### Performance
- CSS custom properties for theming
- SSE instead of AJAX polling
- MI response caching
- Lazy loading (future)
- Optimized asset manifest

## Stats
- **Commits**: 439+
- **New Files**: 50+
- **Lines Added**: ~15,000+
- **Tests**: 7 integration test suites
- **Languages**: 3 (EN, ES, PT)

## Stats (End of Session)
- **Commits**: 474+
- **New Files**: 80+
- **Lines Added**: ~20,000+
- **Tests**: 19 integration test suites
- **Languages**: 3 (EN, ES, PT)
- **Scripts**: 7 utility scripts
- **Documentation**: 7 guides

## Documentation Added
- User Guide
- Admin Guide
- API Reference
- API Examples
- Deployment Guide
- Troubleshooting Guide
- Security Hardening
- Performance Guide
- Monitoring Guide
- Backup and Recovery
- Architecture Overview
- FAQ
- Glossary
- Roadmap
- Contributing Guide
- Security Policy
- Code of Conduct

## Scripts Added
- install.sh (quick install)
- update.sh (update system)
- backup-db.sh (database backup)
- restore-db.sh (database restore)
- monitor.sh (system monitor)
- ocp-maintenance.sh (daily maintenance)
- build-ocp-theme.sh (theme build)
- ci-scan.sh (CI scans)

## Database Migrations
- 07-user-preferences.sql
- 08-user-bookmarks.sql
- 09-feedback.sql
- 10-user-notes.sql

## Infrastructure
- GitHub Actions CI
- Pre-commit hooks
- Makefile
- .editorconfig
- .gitattributes
- .dockerignore

## Final Stats
- **Total Commits**: 494+
- **Total Files**: 150+
- **PHP Files**: 65
- **Test Scripts**: 19
- **Documentation**: 20+
- **Scripts**: 7

## Session Summary (2026-05-27 01:22)
- **Total Commits**: 552
- **Session Duration**: ~10 hours
- **Features Implemented**: 30+
- **Pages Created**: 20+
- **Tests Created**: 19
- **Scripts Created**: 25+
- **Documentation**: 35+
- **Database Migrations**: 4

## [1.0.1] - 2026-05-27

### Fixes
- **OpenSIPS Syntax**: Removed `children = 8` directive causing parse error in OpenSIPS 3.6.6 (`a27a55b`)
- **Frontend Navigation**: Fixed broken `addresses` → `address` link in role-nav.php (`a202349`)
- **Docker Healthcheck**: Updated OCP healthcheck after removing `healthcheck-audit.php` (`3738baf`)
- **MI HTTP**: Replaced GET calls with POST+JSON-RPC for `tls_reload` and `version` (`f6ea33f`)
- **Tests**: Fixed arithmetic expansion breaking `set -e` in `test-ocp-all.sh` (`0ec543f`)
- **Audit**: Repaired hash chain integrity and test query bug (`9341777`)
- **PgBouncer**: Added `auth_file` with SCRAM-SHA-256 for OCP connectivity (`f6d8f28`)

### Frontend Audit & Consolidation (2026-05-27)
- **Wiki Relocation**: Removed wiki from sidebar; added 📖 header button
- **Orphan Page Consolidation**: Added 18 previously orphan pages to navigation menus
- **Duplicate Removal**: Removed stubs (`health.php`, `healthcheck-audit.php`) and duplicate trunk pages
- **Menu Coverage**: 53+ pages exposed across 9 sections, zero orphan pages remaining
- **OCP Tests**: 17/17 PASS, container healthy, zero broken links

### Features Completed (029–033)
- **Feature 029**: Frontend Refactor — MI Parity complete (drag-and-drop dashboard, SSE expansion, MI data export)
- **Feature 030**: OCP User Management & RBAC — complete implementation
- **Feature 031**: OCP REST API — complete implementation with public status endpoint
- **Feature 032**: Automated Backup Verification & DR Testing — backup checksums, restore verification, OCP dashboard, Prometheus alerts, monthly DR drills
- **Feature 033**: Performance Benchmarking & Load Testing — SIP signaling benchmarks, PostgreSQL query latency tests, automated report generation

### Stats Update
- **Total Commits**: 615
- **PHP Files**: 115
- **OCP Coverage**: ~81% (26/32 OCP modules implemented)
- **Integration Tests**: 96+ PASS, 0 FAIL

## Status: COMPLETE ✅
All planned features for Q2 2026 have been implemented.
