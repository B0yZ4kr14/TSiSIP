# TSiSIP Quick Reference

## Commands

### Docker
```bash
make build      # Build images
make up         # Start services
make down       # Stop services
make logs       # View logs
```

### Testing
```bash
make test       # Run tests
bash scripts/lint.sh      # Lint
bash scripts/benchmark.sh # Benchmark
```

### Maintenance
```bash
bash scripts/backup-db.sh     # Backup
bash scripts/restore-db.sh    # Restore
bash scripts/monitor.sh       # Monitor
bash scripts/ocp-maintenance.sh # Maintenance
```

### Utilities
```bash
bash scripts/health-check.sh  # Health
bash scripts/status.sh        # Status
bash scripts/validate.sh      # Validate
bash scripts/report.sh        # Report
```

## URLs

| URL | Description |
|-----|-------------|
| /login.php | Login |
| /dashboard.php | Dashboard |
| /health.php | Health check |
| /system-health.php | System health |
| /audit-log.php | Audit log |
| /api-docs.php | API docs |

## Files

| File | Purpose |
|------|---------|
| .env | Environment |
| docker-compose.yml | Compose |
| Makefile | Commands |
| web/ | Application |
| db/init/ | Migrations |
| docs/ | Documentation |
| tests/ | Tests |
| scripts/ | Scripts |

## Environment

| Variable | Default |
|----------|---------|
| DB_HOST | postgres |
| DB_NAME | opensips |
| DB_USER | opensips |
| HOST_PUBLIC_IP | 127.0.0.1 |

## Secrets

| File | Purpose |
|------|---------|
| db_password | DB password |
| auth_secret | Auth secret |
| topology_secret | Topology secret |

## Support

- Email: devops@tsiapp.io
- Docs: docs/wiki/
- Issues: GitHub
