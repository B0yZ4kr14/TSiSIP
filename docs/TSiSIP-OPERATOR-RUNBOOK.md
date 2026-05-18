# TSiSIP Operator Runbook

## Quick Reference

```bash
# Start the full stack
docker compose up -d

# View logs
docker compose logs -f

# Build OCP theme after design changes
./scripts/build-ocp-theme.sh

# Rollback OCP theme to original OCP v9
./scripts/rollback-ocp-theme.sh

# Check OpenSIPS health
docker compose exec opensips opensips -c -f /etc/opensips/opensips.cfg

# Check PostgreSQL health
docker compose exec postgres pg_isready -U opensips

# Check OCP health
docker compose exec ocp bash -c "curl -fsSL http://localhost/login.php | grep -q 'TSiSIP'"
```

## Architecture Overview

TSiSIP runs as a Docker Compose stack with four services:

| Service | Image | Networks | Published Ports |
|---|---|---|---|
| `opensips` | `tsisip-opensips:latest` | sip_edge, sip_internal, db_internal | 5060/udp, 5060/tcp |
| `rtpengine` | `tsisip/rtpengine:latest` | sip_edge, sip_internal | 10000-20000/udp |
| `postgres` | `postgres:16` | db_internal | (none) |
| `ocp` | `tsisip/ocp:latest` | sip_internal, db_internal | (none) |

**Access OCP**: Via reverse proxy or VPN tunnel to `sip_internal`. OCP has no published ports.

## Daily Operations

### Checking Service Health

```bash
# All services
docker compose ps

# Individual health checks
docker compose exec opensips opensips -c
docker compose exec postgres pg_isready -U opensips
docker compose exec ocp bash -c "curl -fsSL http://localhost/login.php | grep -q 'TSiSIP'"
```

### Rebuilding After Config Changes

**OpenSIPS config** (`opensips/opensips.cfg.tpl`):
```bash
docker compose build opensips
docker compose up -d opensips
```

**OCP theme** (`web/tsisip/`, `build/theme.json`):
```bash
./scripts/build-ocp-theme.sh
docker compose up -d ocp
```

**PostgreSQL schema** (`db/init/*.sql`):
> WARNING: Re-initializing PostgreSQL destroys existing data. Use migrations for production.

```bash
docker compose down -v postgres_data
docker compose up -d postgres
```

## Troubleshooting

### OpenSIPS fails to start

1. Check secrets exist:
   ```bash
   ls secrets/{db_password,auth_secret,topology_secret}
   ```
2. Validate rendered config:
   ```bash
   docker compose exec opensips opensips -c -f /etc/opensips/opensips.cfg
   ```
3. Check env vars in `.env`:
   ```bash
   cat .env
   ```

### OCP shows unstyled / broken page

1. Check asset manifest exists:
   ```bash
   docker compose exec ocp ls -la /var/www/html/tsisip/asset-manifest.json
   ```
2. Rebuild theme:
   ```bash
   ./scripts/build-ocp-theme.sh
   docker compose up -d ocp
   ```
3. Check Apache error log:
   ```bash
   docker compose exec ocp tail -50 /var/log/apache2/error.log
   ```

### D3.js charts not rendering

1. Verify D3.js is loaded on the chart view:
   ```bash
   curl -s http://<ocp-host>/dispatcher.php | grep "d3.v7.min"
   ```
2. Check browser console for JS errors.
3. Ensure `tsisip-charts.js` is served correctly:
   ```bash
   curl -I http://<ocp-host>/tsisip/js/tsisip-charts.js
   ```

## Security Reminders

- Never commit `secrets/` or `.env` files.
- Rotate `auth_secret` and `topology_secret` quarterly.
- PostgreSQL and Asterisk must never have published ports.
- All SIP credentials are HA1 hashes only -- no plaintext passwords.

## Backup & Restore Operations

### Architecture

The `backup` service runs on `db_internal` and performs:
- **Daily logical backup** at 02:00 UTC (`pg_dump -Fc` → gzip → AES-256-CBC + HMAC)
- **WAL archiving** via `archive_command` (gzip + encrypt)
- **Retention purge** at 03:00 UTC (30 days backups, 37 days WAL)
- **Validation** at 04:00 UTC (restore test + row count checks)
- **Offsite replication** hourly via `rclone sync --bwlimit 50M`
- **RPO monitoring** every 5 minutes (`pg_stat_archiver` lag)
- **Quota monitoring** every 10 minutes (auto-purge at 80%, critical alert at 95%)
- **Metrics export** on `:9101/metrics` for Prometheus

### Performing a Manual Backup

```bash
# Trigger immediate backup
docker compose exec backup /usr/local/bin/backup.sh

# View latest backup
docker compose exec backup ls -la /backup/daily/
docker compose exec backup readlink /backup/daily/latest
```

### Restoring from Latest Backup

```bash
# 1. Stop OpenSIPS to prevent writes during restore
docker compose stop opensips

# 2. Identify target backup
docker compose exec backup readlink /backup/daily/latest

# 3. Decrypt and restore to PostgreSQL (replace opensips DB)
docker compose exec backup bash -c '
  BACKUP=$(readlink /backup/daily/latest)
  /usr/local/bin/encrypt.sh decrypt "/backup/daily/$BACKUP" /tmp/restore.dump.gz
  gunzip -c /tmp/restore.dump.gz > /tmp/restore.dump
  PGPASSWORD=$(cat /run/secrets/db_password) pg_restore \
    -h postgres -U opensips -d opensips --clean --no-owner --no-privileges /tmp/restore.dump
  rm -f /tmp/restore.dump /tmp/restore.dump.gz
'

# 4. Restart OpenSIPS
docker compose up -d opensips
```

### Point-in-Time Recovery (PITR)

```bash
# Dry-run: preview WAL segments to be replayed
docker compose exec backup /usr/local/bin/pitr-restore.sh \
  --target "2026-05-16T13:45:00Z" --verify-only

# Execute PITR to a temporary database
docker compose exec backup /usr/local/bin/pitr-restore.sh \
  --target "2026-05-16T13:45:00Z" --temp-db pitr_recovery_20260516

# Verify the temp database
docker compose exec backup pg_isready -h postgres -U opensips -d pitr_recovery_20260516
```

### Validation

```bash
# Run validation manually (structure check only)
docker compose exec backup /usr/local/bin/validate.sh

# Full validation with row-count checks against temp DB
docker compose exec backup bash -c 'FULL_VALIDATE=true /usr/local/bin/validate.sh'

# Check validation metric
docker compose exec backup cat /backup/metrics/validation_status.prom
```

### Offsite Replication Verification

```bash
# List remote backups (requires rclone config)
docker compose exec backup rclone ls remote:tsisip-backups/daily

# Verify checksums on a specific file
docker compose exec backup rclone check /backup/daily remote:tsisip-backups/daily

# Trigger immediate replication
docker compose exec backup /usr/local/bin/replicate.sh
```

### Encryption Key Rotation

```bash
# 1. Place new key in secrets/
echo "$(openssl rand -base64 32)" > secrets/backup_encryption_key_new

# 2. Mount the new secret in docker-compose.yml (temporary) and recreate container
#    Add under backup.secrets: - backup_encryption_key_new

# 3. Dry-run to see affected backups
docker compose exec backup /usr/local/bin/rotate-key.sh --dry-run

# 4. Execute rotation (re-encrypts last 7 days of backups + WAL)
docker compose exec backup /usr/local/bin/rotate-key.sh

# 5. Replace the old key with the new key and remove the temporary secret mount
#    mv secrets/backup_encryption_key_new secrets/backup_encryption_key
#    docker compose up -d backup
```

### Monitoring Backup SLA

```bash
# RPO lag (WAL archiving)
docker compose exec backup cat /backup/metrics/rpo_lag_seconds.prom

# RTO (last restore duration)
docker compose exec backup cat /backup/metrics/rto_last_seconds

# Storage quota
docker compose exec backup cat /backup/metrics/quota_usage.prom

# All metrics via exporter
curl http://backup:9101/metrics
```

## Rate Limiting & DDoS Protection

### Architecture

Feature 006 implements multi-layer rate limiting:
- **pike**: Per-source IP request throttling (50 req / 2s window)
- **ratelimit**: Per-user auth attempt throttling (10 attempts / 60s)
- **userblacklist**: Persistent ban lists via PostgreSQL
- **anomaly-detector**: Statistical anomaly detection sidecar

### Inspecting Current Blocks

```bash
# Check pike blocked sources (via MI)
docker compose exec opensips opensips-cli -x mi get_statistics pike

# Check ratelimit pipe status
docker compose exec opensips opensips-cli -x mi get_statistics rl_stats

# List userblacklist entries
docker compose exec postgres psql -U opensips -c "SELECT * FROM userblacklist;"
```

### Manually Banning a Source

```bash
# Add IP or user to blacklist
docker compose exec postgres psql -U opensips -c "INSERT INTO userblacklist (username, domain, prefix, whitelist) VALUES ('attacker', '', '1', 0);"

# Reload blacklist in OpenSIPS
docker compose exec opensips opensips-cli -x mi reload_blacklist
```

### Unbanning

```bash
# Remove from blacklist
docker compose exec postgres psql -U opensips -c "DELETE FROM userblacklist WHERE username = 'attacker';"

# Reload
docker compose exec opensips opensips-cli -x mi reload_blacklist
```

### Tuning Pike Thresholds

Edit `opensips/opensips.cfg.tpl`:
```
modparam("pike", "sampling_time_unit", 2)
modparam("pike", "reqs_density_per_unit", 50)
```

Rebuild and restart OpenSIPS:
```bash
docker compose build opensips
docker compose up -d opensips
```

### Anomaly Detector

```bash
# View anomaly detector logs
docker compose logs -f anomaly-detector

# Check current z-score
curl -s http://anomaly-detector:8080/metrics | grep z_score
```

### Troubleshooting False Positives

1. **Enterprise PBX behind NAT blocked**: Add trusted IP to `permissions` address table:
   ```bash
   docker compose exec postgres psql -U opensips -c "INSERT INTO address (grp, ip_addr, mask, port, tag) VALUES (1, '203.0.113.10', 32, 0, 'trusted_pbx');"
   ```

2. **Auth rate limit too aggressive**: Increase `rl_check` limit in `route[AUTH]`.

3. **Global throttle misfiring**: Adjust `rl_check("global", 500, ...)` threshold.

## Rollback Procedures

### OCP Theme Rollback

```bash
./scripts/rollback-ocp-theme.sh
./scripts/build-ocp-theme.sh
docker compose up -d ocp
```

### Full Stack Rollback

```bash
docker compose down
git checkout <known-good-commit>
docker compose build
docker compose up -d
```
