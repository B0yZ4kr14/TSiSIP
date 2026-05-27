# TSiSIP Utility Scripts

## Overview

This directory contains operational scripts for TSiSIP deployment, maintenance, and monitoring.

## Scripts

### `backup-db.sh`
**Purpose**: Create compressed database backups  
**Usage**: `./backup-db.sh`  
**Output**: `backups/tsisip_db_YYYYMMDD_HHMMSS.sql.gz`  
**Retention**: 30 days  
**Schedule**: Daily via cron

### `restore-db.sh`
**Purpose**: Restore database from backup  
**Usage**: `./restore-db.sh <backup_file>`  
**Safety**: Requires "yes" confirmation  
**Warning**: Overwrites current database

### `monitor.sh`
**Purpose**: System health monitoring  
**Usage**: `./monitor.sh`  
**Checks**:
- Health endpoint
- Component status
- Disk space (>75% warn, >90% critical)
- Memory usage (>90% warn)
- Container status
**Output**: `logs/alerts.log`  
**Schedule**: Every 5 minutes via cron

### `ocp-maintenance.sh`
**Purpose**: Daily maintenance tasks  
**Usage**: `./ocp-maintenance.sh`  
**Tasks**:
- Clean old audit logs (>90 days)
- Clean expired PHP sessions
- Vacuum and analyze database
- Check disk space
- Health check
- Log rotation
**Output**: `logs/ocp-maintenance-YYYYMMDD.log`  
**Schedule**: Daily via cron

### `build-ocp-theme.sh`
**Purpose**: Build OCP theme assets  
**Usage**: `./build-ocp-theme.sh`

### `ci-scan.sh`
**Purpose**: CI security and quality scans  
**Usage**: `./ci-scan.sh`

## Cron Setup

```bash
# Edit crontab
crontab -e

# Add these lines:
# Backup daily at 2 AM
0 2 * * * /path/to/scripts/backup-db.sh >> /path/to/logs/backup.log 2>&1

# Monitor every 5 minutes
*/5 * * * * /path/to/scripts/monitor.sh >> /path/to/logs/monitor.log 2>&1

# Maintenance daily at 3 AM
0 3 * * * /path/to/scripts/ocp-maintenance.sh
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TSISIP_HEALTH_URL` | `http://localhost/health.php` | Health endpoint URL |

## Logs

All scripts log to `logs/` directory:
- `alerts.log` — Monitor alerts
- `backup.log` — Backup operations
- `ocp-maintenance-YYYYMMDD.log` — Daily maintenance

## Troubleshooting

### Permission Denied
```bash
chmod +x scripts/*.sh
```

### Docker Not Found
Ensure Docker and Docker Compose are in PATH.

### Health Check Fails
Verify OCP container is running:
```bash
docker compose ps ocp
```
