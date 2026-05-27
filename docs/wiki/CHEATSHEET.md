# TSiSIP Cheatsheet

## Installation

```bash
git clone https://github.com/B0yZ4kr14/TSiSIP.git
cd TSiSIP
bash scripts/install.sh
```

## Daily Use

```bash
make up      # Start
make down    # Stop
make logs    # Logs
make test    # Test
```

## Development

```bash
bash scripts/dev-setup.sh  # Setup
bash scripts/lint.sh       # Lint
bash scripts/format.sh     # Format
```

## Database

```bash
bash scripts/backup-db.sh  # Backup
bash scripts/restore-db.sh # Restore
bash scripts/migrate.sh    # Migrate
bash scripts/seed.sh       # Seed
```

## Monitoring

```bash
bash scripts/monitor.sh      # Monitor
bash scripts/health-check.sh # Health
bash scripts/status.sh       # Status
```

## Troubleshooting

```bash
bash scripts/validate.sh     # Validate
bash scripts/security-scan.sh # Security
bash scripts/view-logs.sh    # Logs
```

## Git

```bash
bash scripts/git-stats.sh    # Stats
bash scripts/generate-changelog.sh # Changelog
```

## Docker

```bash
docker compose ps       # List
docker compose logs -f  # Logs
docker stats            # Stats
```

## Database CLI

```bash
docker compose exec postgres psql -U opensips
```

## PHP CLI

```bash
docker compose exec ocp php -v
```

## Common Tasks

### Add User
```sql
INSERT INTO ocp_users (username, email, password_hash, role)
VALUES ('user', 'user@tsiapp.io', crypt('pass', gen_salt('bf')), 'readonly');
```

### Reset Password
```sql
UPDATE ocp_users SET password_hash = crypt('newpass', gen_salt('bf')) WHERE username = 'admin';
```

### Check Sessions
```sql
SELECT * FROM ocp_audit_log WHERE action = 'LOGIN_SUCCESS' ORDER BY event_time DESC LIMIT 10;
```

## Shortcuts

| Shortcut | Action |
|----------|--------|
| g d | Dashboard |
| g h | Health |
| g a | Audit |
| g s | Search |
| g p | Profile |
| ? | Help |
| Esc | Close |

## Support

- devops@tsiapp.io
- docs/wiki/
- GitHub Issues
