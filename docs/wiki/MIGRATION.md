# TSiSIP Migration Guide

## From OpenSIPS 3.4

1. Update Docker image
2. Run schema migration
3. Update config
4. Test

## From MySQL

1. Export data
2. Convert schema
3. Import to PostgreSQL
4. Update configs

## From Bare Metal

1. Backup data
2. Create Docker setup
3. Restore data
4. Update DNS

## Database Migration

### Running
```bash
bash scripts/migrate.sh
```

### Order
Files run alphabetically.

### Adding
1. Create `db/init/NN-name.sql`
2. Test locally
3. Commit
4. Deploy

## Configuration Migration

### Environment
```bash
# Old
cp .env.old .env

# New
cp .env.example .env
# Edit values
```

### Secrets
```bash
# Regenerate
openssl rand -base64 32 > secrets/db_password
```

## Data Migration

### Backup
```bash
bash scripts/backup-db.sh
```

### Restore
```bash
bash scripts/restore-db.sh backup.sql.gz
```

## Version Upgrade

### Patch
```bash
bash scripts/bump-version.sh patch
```

### Minor
```bash
bash scripts/bump-version.sh minor
```

### Major
```bash
bash scripts/bump-version.sh major
```

## Rollback

### Database
```bash
bash scripts/restore-db.sh backup.sql.gz
```

### Code
```bash
git revert <commit>
```

### Docker
```bash
docker compose down
docker compose up -d --force-recreate
```

## Testing

### Before
```bash
make test
```

### After
```bash
make test
bash scripts/benchmark.sh
```

## Troubleshooting

### Failed Migration
1. Check logs
2. Fix SQL
3. Re-run
4. Verify

### Data Loss
1. Stop services
2. Restore backup
3. Verify data
4. Resume

## Support

Contact devops@tsiapp.io
