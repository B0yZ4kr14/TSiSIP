# TSiSIP Backup and Recovery

## Backup Strategy

### Automated
- Daily at 2 AM
- 30-day retention
- Gzip compression
- Verification

### Manual
```bash
./scripts/backup-db.sh
```

## Backup Contents

### Database
- All tables
- Schema
- Indexes
- Data

### Configuration
- .env
- Docker configs
- Nginx configs

### Secrets
- db_password
- auth_secret
- topology_secret

## Storage

### Local
```
backups/
├── tsisip_db_20260527_020000.sql.gz
├── tsisip_db_20260526_020000.sql.gz
└── ...
```

### Remote
- S3 bucket
- SFTP server
- NFS share

## Recovery

### Full Recovery
```bash
# Stop services
docker compose down

# Restore database
./scripts/restore-db.sh backups/tsisip_db_YYYYMMDD_HHMMSS.sql.gz

# Start services
docker compose up -d

# Verify
curl http://localhost/health.php
```

### Partial Recovery
```bash
# Restore specific table
gunzip -c backup.sql.gz | grep -A 100 "CREATE TABLE subscriber" | docker compose exec -T postgres psql -U opensips
```

## Testing

### Verify Backup
```bash
gunzip -t backup.sql.gz
```

### Test Restore
```bash
# Create test database
docker compose exec postgres createdb -U opensips test_restore

# Restore
gunzip -c backup.sql.gz | docker compose exec -T postgres psql -U opensips test_restore

# Verify
docker compose exec postgres psql -U opensips test_restore -c "SELECT COUNT(*) FROM subscriber;"

# Cleanup
docker compose exec postgres dropdb -U opensips test_restore
```

## Disaster Recovery

### Scenario: Database Corruption
1. Stop services
2. Restore from backup
3. Verify data integrity
4. Start services
5. Monitor

### Scenario: Server Failure
1. Provision new server
2. Install Docker
3. Clone repository
4. Restore secrets
5. Restore database
6. Start services
7. Verify

### Scenario: Ransomware
1. Isolate affected systems
2. Assess damage
3. Restore from clean backup
4. Verify integrity
5. Update security
6. Monitor

## Best Practices

1. Test backups regularly
2. Store offsite
3. Encrypt backups
4. Document procedures
5. Automate where possible
6. Monitor backup status

## Schedule

| Task | Frequency | Retention |
|------|-----------|-----------|
| Full backup | Daily | 30 days |
| Incremental | Hourly | 7 days |
| Archive | Monthly | 1 year |

## Tools

- pg_dump
- gzip
- rsync
- rclone
- cron
