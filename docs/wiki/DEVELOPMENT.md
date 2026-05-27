# TSiSIP Development Guide

## Setup

### Quick Start
```bash
bash scripts/dev-setup.sh
```

### Manual
```bash
# Requirements
docker --version
docker compose version
git --version

# Clone
git clone https://github.com/B0yZ4kr14/TSiSIP.git
cd TSiSIP

# Setup
cp .env.example .env
mkdir -p secrets logs backups cache

# Build
docker compose build

# Start
docker compose up -d

# Migrate
bash scripts/migrate.sh

# Seed
bash scripts/seed.sh
```

## Workflow

### Branching
```bash
git checkout -b feature/my-feature
```

### Commits
```bash
git commit -m "feat: Add feature"
```

### Testing
```bash
make test
```

### Linting
```bash
bash scripts/lint.sh
```

## Structure

```
web/           # PHP application
├── common/    # Shared code
├── tsisip/    # Assets
└── *.php      # Pages
db/init/       # SQL migrations
docs/          # Documentation
tests/         # Test scripts
scripts/       # Utility scripts
```

## Code Style

### PHP
- PSR-12
- 4 spaces
- 120 char limit
- Docblocks

### JavaScript
- Standard
- 4 spaces
- Semicolons

### CSS
- BEM methodology
- Custom properties
- Mobile first

### SQL
- Lowercase keywords
- snake_case
- Comments

## Debugging

### PHP
```php
error_log(print_r($var, true));
```

### Docker
```bash
docker compose logs -f ocp
```

### Database
```bash
docker compose exec postgres psql -U opensips
```

## Tools

### VS Code
- PHP Intelephense
- Prettier
- ESLint
- Docker

### CLI
```bash
make build    # Build
make test     # Test
make backup   # Backup
make monitor  # Monitor
```

## Tips

1. Use make targets
2. Run lint before commit
3. Test on mobile
4. Check accessibility
5. Document changes

## Resources

- [OpenSIPS Docs](https://opensips.org)
- [PostgreSQL Docs](https://postgresql.org)
- [Docker Docs](https://docs.docker.com)
