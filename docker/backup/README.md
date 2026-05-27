# TSiSIP Backup Service

Automated PostgreSQL backup with S3 upload support.

## Features

- Daily automated dumps with `pg_dump`
- Gzip compression
- Optional AES-256-CBC encryption
- S3-compatible storage via rclone
- Retention enforcement

## Build

```bash
docker build -t tsisip/backup:latest -f docker/backup/Dockerfile .
```

## Environment

- `BACKUP_RETENTION_DAYS`: Days to keep local backups (default: 30)
- `LGPD_RETENTION_DAYS`: LGPD-mandated retention (default: 365)
- `S3_BUCKET`: Target S3 bucket
- `S3_ENDPOINT`: S3-compatible endpoint
