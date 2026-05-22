# Bugs — TSiSIP Durable Memory

## Resolved Bugs

### BUG-001: systemd-resolved Conflicts with Docker Embedded DNS
- **Date**: 2026-05-19
- **Severity**: MEDIUM
- **Symptom**: External domain resolution fails inside containers (SERVFAIL for 127.0.0.11)
- **Root Cause**: systemd-resolved binds port 53 on host, conflicting with Docker's embedded DNS
- **Fix**: Use `--network host` for certbot; permanent fix pending
- **Prevention**: Document in deployment runbook; verify DNS before cert renewal

### BUG-002: certbot-exporter UnboundLocalError
- **Date**: 2026-05-19
- **Severity**: LOW
- **Symptom**: Python exception in certbot-exporter container
- **Root Cause**: Unbound variable in exporter script
- **Fix**: Removed container; using manual host-network certbot instead
- **Prevention**: Add unit tests for exporter before re-enabling

### BUG-003: OpenSIPS Config Template Missing db_postgres DSN Render
- **Date**: 2026-05-18
- **Severity**: HIGH
- **Symptom**: OpenSIPS fails to start with "undefined variable DB_HOST"
- **Root Cause**: opensips.cfg.tpl did not use envsubst for db_postgres DSN
- **Fix**: Updated template with `#!define DBURL "postgres://..."` using envsubst
- **Prevention**: Validate rendered config in CI before image push

## Active Bugs

### BUG-004: backup-1 Container Unhealthy Until First .enc Backup
- **Date**: 2026-05-19
- **Severity**: LOW
- **Symptom**: Health check fails on fresh deploy
- **Root Cause**: Health script checks for existence of .enc backup file
- **Workaround**: Create dummy backup or ignore until first scheduled backup runs
- **Planned Fix**: Adjust health check to accept "no backups yet" state

### BUG-005: validate-input.php Orphaned in OCP Admin Tools
- **Date**: 2026-05-19
- **Severity**: LOW
- **Symptom**: web/common/validate-input.php exists but subscribers.php and dispatcher.php use inline validation
- **Root Cause**: Integration task was deprioritized during implementation
- **Workaround**: Inline validation is functionally equivalent
- **Planned Fix**: Refactor subscribers.php and dispatcher.php to require validate-input.php

## Bug Patterns to Watch

1. **Docker DNS conflicts** — always verify resolv.conf before network-dependent containers
2. **Template rendering gaps** — envsubst variables must be validated in entrypoint
3. **Orphaned utilities** — create integration task whenever a reusable helper is added
4. **Health check assumptions** — new containers may not have expected artifacts
