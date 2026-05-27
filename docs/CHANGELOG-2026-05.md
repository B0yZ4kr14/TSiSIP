# TSiSIP Changelog — May 2026

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
