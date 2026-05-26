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

# Deploy to VPS (Feature 009)
./deploy/scripts/orchestrate-deploy.sh

# Deploy with build-on-target fallback
FALLBACK_BUILD_ON_TARGET=1 ./deploy/scripts/orchestrate-deploy.sh
```

## Architecture Overview

TSiSIP has two operational profiles:

- `docker-compose.yml`: full development/integration topology.
- `docker-compose.vps.yml`: production VPS-lite+PBX profile used on TSiAPP.

The current TSiAPP VPS production profile runs seven services:

| Service | Image | Networks | Published Ports |
|---|---|---|---|
| `opensips` | `ghcr.io/b0yz4kr14/tsisip/opensips:latest` | sip_edge, sip_internal, db_internal | 5060/udp, 5060/tcp, 5061/tcp |
| `rtpengine` | `ghcr.io/b0yz4kr14/tsisip/rtpengine:latest` | sip_edge, sip_internal | 10000-10999/udp |
| `postgres` | `ghcr.io/b0yz4kr14/tsisip/postgres:latest` | db_internal | (none) |
| `asterisk-pbx-1` | `ghcr.io/b0yz4kr14/tsisip/asterisk:latest` | sip_internal | (none) |
| `asterisk-pbx-2` | `ghcr.io/b0yz4kr14/tsisip/asterisk:latest` | sip_internal | (none) |
| `ocp` | `ghcr.io/b0yz4kr14/tsisip/ocp:latest` | sip_internal, db_internal, metrics_host | 127.0.0.1:8084/tcp (requires userland-proxy=true; VPS uses container bridge IP via nginx) |
| `backup` | `ghcr.io/b0yz4kr14/tsisip/backup:latest` | db_internal, metrics_host | internal only (metrics_host network at `backup:9101`) |

**SIP public exposure status**: OpenSIPS listens locally on `5060/udp`, `5060/tcp`, and `5061/tcp`. External scans still show 5060/5061 as filtered; prior packet capture showed packets do not reach the VPS host, so the remaining public SIP exposure work is upstream of the host.

## OCP Access

Public HTTPS is via the existing Nginx location `https://tsiapp.io/TSiSIP/`. The container publishes `127.0.0.1:8084/tcp` when Docker userland-proxy=true; on the VPS (userland-proxy=false for RTPengine performance), nginx proxies to the container's Docker bridge IP directly.

The OCP dashboard and the Professional Premium Wiki share the same authenticated session. Navigate to:

- **Login**: `https://tsiapp.io/TSiSIP/login.php`
- **Dashboard**: `https://tsiapp.io/TSiSIP/dashboard.php`
- **Wiki**: `https://tsiapp.io/TSiSIP/Wiki`
- **Logout**: `https://tsiapp.io/TSiSIP/logout.php`

### OCP Container Operational Note

The OCP container is managed by `docker compose` on the TSiAPP VPS. Previous network state inconsistencies were resolved by ensuring the compose file defines all networks explicitly and containers are restarted through compose rather than manual `docker run`.

**If OCP needs restart**:
```bash
docker compose up -d ocp
```

**If compose network is inconsistent**:
```bash
# Force recreate networks and containers
docker compose down
docker compose up -d
```

### Default Admin Credentials

| Field | Value |
|---|---|
| Username | `Admin` |
| Password | `admin123!` |
| Role | `admin` |

> **Security Warning**: Change the default password immediately after first login. See **Admin Password Management** below.

### Session Cookie Security

The OCP applies the following PHP session hardening via `docker/ocp/php-session-security.ini`:

| Directive | Value | Purpose |
|---|---|---|
| `session.cookie_httponly` | `1` | Prevents JavaScript access to session cookie |
| `session.cookie_samesite` | `"Strict"` | CSRF protection — cookie never sent on cross-site requests |
| `session.use_strict_mode` | `1` | Prevents session fixation attacks |
| `session.gc_maxlifetime` | `3600` | Session expires after 1 hour of inactivity |

Because the OCP container receives HTTP traffic from the Nginx reverse proxy, `session.cookie_secure` cannot rely on PHP seeing HTTPS directly. The OCP detects HTTPS via the `X-Forwarded-Proto` header set by Nginx and enables `session.cookie_secure` dynamically in `common/config.php`.

Verify the flag is present in the browser:
1. Open DevTools → Application → Cookies → `https://tsiapp.io`
2. Look for `PHPSESSID` — it must have `Secure`, `HttpOnly`, and `SameSite=Strict`

Ensure Nginx forwards the protocol:
```nginx
proxy_set_header X-Forwarded-Proto $scheme;
```

## OCP Admin Tools

The following administrative tools are available to `admin` and `devops` roles via the OCP sidebar under **Admin Tools**:

### Subscriber Management

**URL**: `https://tsiapp.io/TSiSIP/subscribers.php`

Full CRUD on the OpenSIPS `subscriber` table via the Admin API proxy layer. Passwords are never stored in plaintext; instead, HA1 hashes (MD5, SHA-256, SHA-512/256) are generated automatically in the OCP and sent to the proxy for database write.

| Action | How |
|---|---|
| List | Default view shows up to 25 subscribers per page (read directly from DB) |
| Create | Fill username, domain, password, tenant, routing group, enabled |
| Edit | Click **Edit** on any row; password change is optional |
| Delete | Click **Delete** and confirm |

**Security notes**:
- All mutating actions require a valid CSRF token.
- Subscriber mutations (create/update/delete) are delegated to the `admin-api` proxy service over the internal Docker network.
- The proxy validates HA1 hash format (hex, exact length) and rejects plaintext passwords.
- Rate limiting: max 10 creations/min, 30 updates/min, 10 deletions/min per source IP.
- The `password` column in `subscriber` is always empty (`''`); only `ha1`, `ha1_sha256`, and `ha1_sha512t256` are populated.

**Troubleshooting**:
- If subscriber operations fail with "Subscriber service temporarily unavailable", check that the `admin-api` container is healthy: `docker compose ps admin-api`
- Verify the proxy service secret is mounted: `docker compose exec admin-api ls -la /run/secrets/proxy_api_secret`
- Check proxy audit logs in PostgreSQL: `SELECT * FROM auth_audit_log WHERE user_id = 'admin-api' ORDER BY event_time DESC LIMIT 10;`

### CDR Viewer

**URL**: `https://tsiapp.io/TSiSIP/cdr-viewer.php`

Read-only view of Call Detail Records from the `cdr` table. Supports filtering by date range and SIP response code.

| Filter | Description |
|---|---|
| From / To | Date range (inclusive) |
| SIP Code | Exact match on `sip_code` (e.g., `200`, `404`, `486`) |

Pagination shows 25 records per page. The table schema follows the stock OpenSIPS CDR module: `start_time`, `end_time`, `duration`, `sip_code`, `sip_reason`, `setuptime`.

### Dispatcher Targets

**URL**: `https://tsiapp.io/TSiSIP/dispatcher.php`

Full CRUD on the OpenSIPS `dispatcher` table. Replaces the previous hard-coded HTML stub.

| Field | Description |
|---|---|
| `setid` | Dispatcher set ID (integer) |
| `destination` | SIP URI of the backend (e.g., `sip:10.0.0.1:5060`) |
| `state` | `0` = active, `1` = inactive |
| `weight` | Load-balancing weight |
| `priority` | Failover priority within the set |
| `attrs` | Optional module attributes |
| `description` | Human-readable label |

Changes take effect on the next OpenSIPS reload or restart.

### Dialplan Manager

**URL**: `https://tsiapp.io/TSiSIP/dialplan.php`

Full CRUD on the OpenSIPS `dialplan` table. Supports database-driven dialplan rules without editing `opensips.cfg.tpl`.

| Field | Description |
|---|---|
| `pr` | Priority (integer, lower = first match) |
| `match_op` | Match operator (`0` = equal, `1` = regexp, `2` = fnmatch) |
| `match_exp` | Expression to match against input |
| `match_flags` | Flags modifier |
| `subst_exp` | Substitution expression (regexp) |
| `repl_exp` | Replacement expression |
| `attrs` | Optional attributes passed to script |

**Security notes**:
- All mutating actions require a valid CSRF token.
- PDO prepared statements are used for all queries.

### Domains Manager

**URL**: `https://tsiapp.io/TSiSIP/domains.php`

Full CRUD on the OpenSIPS `domain` table. Manages SIP domains recognized by OpenSIPS.

| Field | Description |
|---|---|
| `domain` | SIP domain name (e.g., `tsiapp.io`) |
| `did` | Domain ID (optional) |
| `last_modified` | Automatically set by PostgreSQL on changes |

**Security notes**:
- All mutating actions require a valid CSRF token.
- PDO prepared statements are used for all queries.

### Dialog Viewer

**URL**: `https://tsiapp.io/TSiSIP/dialog.php`

Read-only view of active SIP dialogs (ongoing calls). Queries the OpenSIPS `dialog` module via MI `dlg_list` or the PostgreSQL `dialog` table.

| Column | Description |
|---|---|
| `callid` | SIP Call-ID |
| `from_uri` / `to_uri` | Caller and callee URIs |
| `state` | `Early`, `Confirmed`, `Terminated`, `Deleted` |
| `duration` | Call duration in HH:MM:SS |
| `start_time` | UNIX timestamp of dialog creation |

**Security notes**:
- Read-only — no mutation of active calls is permitted.
- Requires `devops` role.

### MI Commands Runner

**URL**: `https://tsiapp.io/TSiSIP/mi-commands.php`

Execute whitelisted OpenSIPS Management Interface (MI) commands via the web UI.

| Command | Role | Description |
|---|---|---|
| `dlg_list` | devops+ | List active dialogs |
| `get_statistics` | devops+ | Retrieve module statistics |
| `ds_reload` | devops+ | Reload dispatcher sets from DB |
| `domain_reload` | devops+ | Reload domain table from DB |
| `dlg_end_dlg` | admin only | Terminate a specific dialog (requires hash_entry + hash_id) |
| `tls_reload` | admin only | Reload TLS certificates without restart |

**Security notes**:
- Only whitelisted commands may be executed; non-whitelisted commands are rejected with HTTP 403.
- `dlg_end_dlg` and `tls_reload` require `admin` role.
- All executions are logged to `auth_audit_log`.
- MI output is sanitized with `htmlspecialchars()` before display.

### Statistics Monitor

**URL**: `https://tsiapp.io/TSiSIP/statistics.php`

Real-time dashboard of key OpenSIPS metrics using D3.js charts. Auto-refreshes every 30 seconds.

| Metric | Source |
|---|---|
| UAS Transactions | `tm:UAS_transactions` |
| UAC Transactions | `tm:UAC_transactions` |
| 1xx Replies | `sl:1xx_replies` |
| 2xx Replies | `sl:2xx_replies` |
| 3xx Replies | `sl:3xx_replies` |
| 4xx Replies | `sl:4xx_replies` |
| 5xx Replies | `sl:5xx_replies` |

**Operational notes**:
- The `?ajax=1` endpoint returns JSON for programmatic consumption.
- Requires `devops` role.

### TLS Management

**URL**: `https://tsiapp.io/TSiSIP/tls-management.php`

View loaded TLS certificates and trigger hot reload.

| Action | Role | Effect |
|---|---|---|
| View certificates | devops+ | Display domain, cert file, key file, CA file, verify settings |
| Reload certificates | admin only | Execute `tls_reload` MI command; no container restart required |

**Operational notes**:
- Update certificate files in `secrets/` (mounted into the OpenSIPS container), then trigger reload.
- Reload is logged to `auth_audit_log`.
- Requires `admin` role for reload; `devops` can view only.

## Wiki Navigation

The TSiSIP Professional Premium Wiki is available at `https://tsiapp.io/TSiSIP/Wiki`.

### TOC Sidebar

Use the table-of-contents sidebar on the left to switch between wiki pages. Pages are grouped by audience and functional area.

### Search and Filter

Use the search/filter field at the top of the sidebar to narrow pages by keyword. The filter applies to page titles and section headings.

### Role-Based Page Visibility

Wiki pages are filtered based on the authenticated session role. Each operator sees only the pages relevant to their responsibilities. See the **Role-Based Access** section below for the mapping.

## Role-Based Access

Access to the wiki and OCP features is determined by the authenticated session role.

| Role | Wiki Access | OCP Features | Escalation Path |
|---|---|---|---|
| Admin | Full access — all wiki pages, settings, and administrative functions | Full | Direct — admin owns all escalation paths |
| DevOps | Technical operations pages (SIP, media, routing, backup, observability) | Technical ops, monitoring, logs | Admin for infrastructure changes |
| Dentist | Clinical guides, endpoint verification, call quality monitoring | Clinical dashboard, quality metrics | Admin for infrastructure; DevOps for SIP issues |
| Assistant | Front-desk guides, daily health checks, trunk verification, patient call routing | Front-desk ops, basic health | Admin or DevOps for technical faults |
| User / Readonly | General operational info, system overview, operator guides | Read-only dashboard, public metrics | Admin for access expansion |

### How Role Is Determined

Role is established at session creation based on the authenticated user identity. The OCP session enforces the role for the lifetime of the session. Changing roles requires re-authentication with a different account.

### Admin Password Management

**Forced password change on first login**:

The OCP enforces a mandatory passphrase change for any account with `force_password_change = TRUE`. After authentication, users are redirected to `change-password.php` and cannot access other pages until a strong passphrase is set.

Passphrase requirements:
- Minimum 12 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one symbol

**Reset force_password_change flag manually**:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
UPDATE ocp_users
SET force_password_change = TRUE,
    updated_at = NOW()
WHERE username = 'Admin';
"
```

**Change admin password via SQL**:
```bash
# Connect to PostgreSQL
docker compose exec postgres psql -U opensips -d opensips -c "
UPDATE ocp_users
SET password_hash = crypt('NEW_PASSWORD_HERE', gen_salt('bf', 12)),
    force_password_change = FALSE,
    updated_at = NOW()
WHERE username = 'Admin';
"
```

**Create a new admin user**:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
INSERT INTO ocp_users (username, email, password_hash, role, enabled, force_password_change)
VALUES (
    'newadmin',
    'newadmin@tsisip.local',
    crypt('SecurePass123!', gen_salt('bf', 12)),
    'admin',
    true,
    true
)
ON CONFLICT (username) DO NOTHING;
"
```

**Unlock a locked account**:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
UPDATE ocp_users
SET failed_attempts = 0,
    locked_until = NULL,
    updated_at = NOW()
WHERE username = 'LOCKED_USERNAME';
"
```

**View login audit log**:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
SELECT event_time, username, source_ip, result, reason
FROM ocp_login_log
ORDER BY event_time DESC
LIMIT 20;
"
```

### Escalation Paths

- **Infrastructure or SIP faults**: Assistant → DevOps → Admin
- **Clinical or quality issues**: Dentist → Admin (with DevOps looped in for media/SIP)
- **Access or permissions issues**: Any role → Admin

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

### OCP login fails

1. **Invalid credentials error**:
   ```bash
   # Verify user exists and is enabled
   docker compose exec postgres psql -U opensips -d opensips -c "
   SELECT username, role, enabled, failed_attempts, locked_until
   FROM ocp_users WHERE LOWER(username) = LOWER('Admin');
   "
   ```

2. **Account locked**:
   ```bash
   # Check if locked_until is in the future
   docker compose exec postgres psql -U opensips -d opensips -c "
   SELECT username, locked_until FROM ocp_users WHERE failed_attempts > 0;
   "
   # Unlock: UPDATE ocp_users SET failed_attempts=0, locked_until=NULL WHERE username='Admin';
   ```

3. **Database connection error** (OCP container logs show "Service temporarily unavailable"):
   ```bash
   # Verify secret is readable
   docker compose exec ocp ls -la /tmp/db_password
   # Verify PostgreSQL password
   docker compose exec postgres psql -U opensips -d opensips -c "SELECT 1"
   # If password mismatch, reset: ALTER USER opensips WITH PASSWORD '...';
   ```

4. **Session not persisting**:
   - Check browser cookie settings (must accept `PHPSESSID`)
   - Verify OCP container has write access to `/tmp` for session storage

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

# All metrics via Docker metrics_host network (userland-proxy=false removes host loopback access)
docker run --rm --network tsisip_metrics_host alpine wget -qO- http://backup:9101/metrics
```

The RPO monitor emits both `backup_rpo_lag_seconds` and `backup_current_wal_info`. If `current_wal` equals `last_archived_wal`, the database is idle or caught up and RPO lag is reported as `0`.

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

## Asterisk PBX Backend Operations (Feature 007)

### Architecture

OpenSIPS routes authenticated calls to Asterisk backends via the dispatcher module:

| Backend | Dispatcher Set | Weight | Network |
|---------|---------------|--------|---------|
| `asterisk-pbx-1` | 1 | 50 | sip_internal |
| `asterisk-pbx-2` | 1 | 50 | sip_internal |

### Verify Dispatcher State

```bash
# Check dispatcher entries in PostgreSQL
docker compose exec postgres psql -U opensips -d opensips -c "SELECT setid, destination, state, weight, description FROM dispatcher;"

# Check dispatcher status via OpenSIPS MI
docker compose exec opensips opensipsctl dispatcher dump
```

### Asterisk Configuration

Asterisk configs are mounted from `docker/asterisk/`:
- `pjsip.conf` — PJSIP trunk configuration for OpenSIPS
- `extensions.conf` — Dialplan for inbound calls

```bash
# Reload Asterisk config without restart
docker compose exec asterisk-pbx-1 asterisk -rx "core reload"
docker compose exec asterisk-pbx-1 asterisk -rx "pjsip show endpoints"
```

### End-to-End Call Flow Verification

```bash
# 1. Verify OpenSIPS is listening
docker compose exec opensips opensips -c

# 2. Verify Asterisk health
docker compose ps asterisk-pbx-1 asterisk-pbx-2

# 3. Run integration tests
python3 -m pytest tests/integration/test_end_to_end_call.py -v

# 4. Manual SIP test (requires sipsak or similar)
docker run --rm --network tsisip_sip_edge alpine \
  sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && \
         sipsak -U -s sip:devuser@dev.tsisip.local:5060 -a devpass -vv"
```

### Failover Testing

```bash
# Stop pbx-1 and verify calls route to pbx-2
docker compose stop asterisk-pbx-1
docker compose logs -f opensips | grep "FAILOVER\|Selected dispatcher"

# Restore pbx-1
docker compose start asterisk-pbx-1
```

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `no dispatching data in the db` | Dispatcher table empty | Verify seed data loaded: `SELECT * FROM dispatcher;` |
| `403 Forbidden` from Asterisk | IP not in identify range | Check `opensips-identify` match ranges in `pjsip.conf` |
| `484 Temporarily Unavailable` | All backends down | Start Asterisk containers, check dispatcher state |
| No audio | RTPengine not running | `docker compose ps rtpengine`, verify UDP 10000-20000 |

## Anomaly Detection Operations (Feature 008)

### Architecture

OpenSIPS event routes (`E_PIKE_BLOCKED`, `E_AUTH_FAILURE`, `E_DISPATCHER_STATUS`) log events that the anomaly detector consumes to establish a statistical baseline.

| Metric | Window | Threshold | Action |
|--------|--------|-----------|--------|
| Z-Score | 60s | 3.0 | Log alert |
| Z-Score | 60s | 6.0 | Critical alert to Alertmanager |
| Consecutive alerts | 2 windows | — | Send webhook to Alertmanager |

### Viewing Anomaly Metrics

```bash
# Current detector status
curl -s http://localhost:8080/api/v1/status | jq .

# Prometheus metrics
curl -s http://localhost:8080/metrics | grep tsisip_anomaly

# Grafana dashboard
# Navigate to "TSiSIP - Anomaly Detection" dashboard
```

### Alertmanager Integration

The detector sends alerts to Alertmanager at `http://alertmanager:9093/api/v1/alerts`:

```bash
# Verify Alertmanager is receiving alerts
curl -s http://localhost:9093/api/v1/alerts | jq '.data[] | select(.labels.alertname == "TSiSIPAnomaly")'
```

### Simulating an Attack (Testing)

```bash
# Send a burst of events to trigger an alert
for i in $(seq 1 500); do
    curl -s -X POST http://localhost:8080/api/v1/event \
        -H "Content-Type: application/json" \
        -d '{"event_type":"E_AUTH_FAILURE","source_ip":"192.0.2.1","sip_method":"INVITE"}'
done

# Wait 2 analysis windows (120s) and check for alerts
curl -s http://localhost:8080/api/v1/status | jq .
```

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Z-score always 0 | Not enough baseline samples | Wait 24h for baseline or seed with synthetic data |
| No alerts firing | Z-score below threshold | Verify event volume; check `tsisip_current_rps` |
| Alertmanager errors | Network unreachable | Verify `ALERTMANAGER_URL` env var in compose |
| High false positives | Baseline too narrow | Increase `BASELINE_WINDOW_HOURS` to 48+ |

## Multi-Tenant Header Routing (Feature 002)

### Architecture

OpenSIPS routes calls through a priority system:

1. **Header Routing Rules** — `X-Route-Key` header match in `header_routing_rules`
2. **Subscriber Routing Group** — `routing_group` column from authenticated subscriber
3. **Default Set** — Fallback to dispatcher set `1`

### Tenant Isolation

Each tenant has:
- Unique `sip_domain` in `tenants` table
- Scoped `subscriber` entries (FK via `tenant_id`)
- Scoped `header_routing_rules` for per-tenant routing
- Scoped `pbx_backends` for PBX pool ownership

### Managing Routing Rules

```bash
# List active routing rules for a tenant
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT h.header_name, h.match_value, h.dispatcher_setid, h.priority
    FROM header_routing_rules h
    JOIN tenants t ON h.tenant_id = t.id
    WHERE t.sip_domain = 'dev.tsisip.local' AND h.enabled = true
    ORDER BY h.priority;
"

# Add a new routing rule
docker compose exec postgres psql -U opensips -d opensips -c "
    INSERT INTO header_routing_rules (tenant_id, header_name, match_value, dispatcher_setid, priority)
    SELECT id, 'X-Route-Key', 'premium-v2', 2, 5
    FROM tenants WHERE sip_domain = 'dev.tsisip.local';
"

# Disable a rule
docker compose exec postgres psql -U opensips -d opensips -c "
    UPDATE header_routing_rules SET enabled = false
    WHERE match_value = 'standard';
"
```

### Managing PBX Backends per Tenant

```bash
# List PBX backends for a tenant
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT p.label, p.dispatcher_setid, p.enabled
    FROM pbx_backends p
    JOIN tenants t ON p.tenant_id = t.id
    WHERE t.sip_domain = 'dev.tsisip.local';
"
```

### Testing Tenant Isolation

```bash
# Register as devuser@dev.tsisip.local (should route to set 1)
sipsak -U -s sip:devuser@dev.tsisip.local:5060 -a devpass -vv

# Register with X-Route-Key header (should match routing rule)
# Requires custom SIP client or sipp scenario
```

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `480 Temporarily Unavailable` | Dispatcher set empty | Verify `dispatcher` table has backends for the set |
| `404 Not Here` | In-dialog route failure | Check Record-Route and loose_route |
| Calls land on wrong PBX | Header rule misconfigured | Check `header_routing_rules` priority and match_value |
| Tenant data leaking | Missing WHERE tenant_id | Audit queries for tenant scoping |

## WebRTC / WebSocket Support (Feature 003)

### Architecture

OpenSIPS exposes WebSocket (ws) and secure WebSocket (wss) transports for browser-based SIP clients:

| Transport | Port | Use Case |
|-----------|------|----------|
| `ws` | 8080/tcp | WebRTC clients on trusted/internal networks |
| `wss` | 4443/tcp | WebRTC clients on public internet (TLS) |

### Browser Client Configuration

```javascript
// SIP.js or JsSIP example configuration
const ua = new SIP.UA({
    uri: 'sip:devuser@dev.tsisip.local',
    wsServers: 'wss://dev.tsisip.local:4443',
    register: true,
    traceSip: true
});
```

### Verifying WebSocket Listeners

```bash
# Check OpenSIPS listeners
docker compose exec opensips opensips -c | grep -E 'ws|wss'

# Test ws connection (from internal network)
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" \
    -H "Sec-WebSocket-Key: $(openssl rand -base64 16)" \
    -H "Sec-WebSocket-Version: 13" \
    http://opensips:8080

# Test wss connection (requires TLS)
# Use a WebRTC client or wscat
```

### RTPengine ICE for WebRTC

WebRTC requires ICE candidates. OpenSIPS passes ICE flags to RTPengine:

```opensips
# In route[HANDLE_INVITE] for WebRTC calls:
rtpengine_offer("replace-origin replace-session-connection ICE=force");
```

RTPengine generates ICE candidates and handles STUN/DTLS-SRTP negotiation.

### Firewall / Security

- Port 8080 (ws): Restrict to internal/VPN networks if possible
- Port 4443 (wss): Public, ensure valid TLS certificates
- WebRTC clients must use `wss` (TLS) for production

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `404 Not Found` on ws | proto_ws not loaded | Verify `loadmodule "proto_ws.so"` in config |
| TLS handshake fails on wss | Invalid/missing certs | Check `tls_mgm` certificates and CA list |
| No audio in WebRTC | ICE failure | Verify RTPengine ICE flags; check UDP 10000-20000 |
| Browser blocks ws | Mixed content policy | Use `wss://` (TLS) when page is HTTPS |

## CDR / Billing Foundation (Feature 001)

### Architecture

The `acc` module logs call details to the PostgreSQL `cdr` table:

| Field | Description |
|-------|-------------|
| `call_id` | SIP Call-ID |
| `call_start` | INVITE timestamp |
| `call_end` | BYE or dialog end timestamp |
| `duration` | Call duration in seconds |
| `from_user` / `to_user` | Caller / callee |
| `source_ip` | Originating IP |
| `call_status` | completed, failed, missed, etc. |
| `tenant_id` | Tenant scope for billing segregation |

### Querying CDRs

```bash
# Recent calls
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT call_id, from_user, to_user, duration, call_status, call_start
    FROM cdr ORDER BY call_start DESC LIMIT 10;
"

# Calls per tenant (billing report)
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT t.name, COUNT(*) as calls, SUM(duration) as total_seconds
    FROM cdr c
    JOIN tenants t ON c.tenant_id = t.id
    WHERE call_start > NOW() - INTERVAL '24 hours'
    GROUP BY t.name;
"

# Failed calls analysis
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT call_status, COUNT(*) FROM cdr
    WHERE call_start > NOW() - INTERVAL '1 hour'
    GROUP BY call_status;
"
```

### Billing Integration

Export CDRs to external billing system:

```bash
# CSV export
docker compose exec postgres psql -U opensips -d opensips -c "
    COPY (SELECT * FROM cdr WHERE call_start > NOW() - INTERVAL '1 day')
    TO '/tmp/cdr_export.csv' WITH CSV HEADER;
"
```

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| No CDR records | `setflag(1)` not set | Verify flag in `HANDLE_INVITE` route |
| CDR table empty | `acc` module not loaded | Check `loadmodule "acc.so"` |
| Missing `call_end` | Dialog not tracked | Verify `create_dialog("B")` |
| Tenant_id NULL | Subscriber missing tenant FK | Check `subscriber.tenant_id` population |

## Deploy Operations (Feature 009)

### Pipeline Architecture

The deploy pipeline is implemented in `deploy/scripts/orchestrate-deploy.sh` as a series of gated stages:

| Stage | Agent | Purpose | Failure Action |
|-------|-------|---------|----------------|
| 0 | Pre-flight | Disk check, registry reachability, OpenSIPS syntax, secrets scan, compose validation | Halt |
| 1 | Impact Analysis | Git diff + blast radius analysis on modified files | Halt (override: `FORCE_DEPLOY=1`) |
| 2 | Builder | Build only images with modified Dockerfiles or dependent configs | Halt |
| 3 | Pusher | Tag and push to GHCR; fallback to build-on-target | Warn + fallback |
| 4 | Deployer | SSH to VPS, git pull, docker compose up | Halt |
| 5 | Verifier | Container health, HTTP probes, SIP OPTIONS, backup metrics | Halt + rollback |

### Deploy Modes

**Standard deploy (GHCR images)**:
```bash
./deploy/scripts/orchestrate-deploy.sh
```

**Build-on-target fallback** (when GHCR push fails):
```bash
# The script auto-detects push failure and sets FALLBACK_BUILD_ON_TARGET=1
# This uses docker-compose.build.yml to build images directly on the VPS
```

**Dry-run (no mutations)**:
```bash
./deploy/scripts/orchestrate-deploy.sh --dry-run
```

**Post-deploy verification only**:
```bash
./deploy/scripts/orchestrate-deploy.sh --live-test
```

### VPS Recovery After Critical Load

If the VPS load average exceeds 50 (e.g., multiple stuck `docker compose up` processes):

```bash
# On the VPS
# 1. Kill stuck docker processes
sudo pkill -f "docker compose up"
sudo pkill -f "docker build"

# 2. Check load
uptime
# Wait until load < 10 before proceeding

# 3. Restart core services in dependency order
sudo docker compose -f docker-compose.prod.yml up -d postgres
sleep 10
sudo docker compose -f docker-compose.prod.yml up -d rtpengine opensips
sudo docker compose -f docker-compose.prod.yml up -d ocp
sudo docker compose -f docker-compose.prod.yml up -d

# 4. Verify health
sudo docker compose -f docker-compose.prod.yml ps
```

### Troubleshooting Deploy Failures

| Symptom | Cause | Fix |
|---------|-------|-----|
| `permission_denied: write_package` | GITHUB_TOKEN lacks package write scope | Use PAT with `write:packages`; or enable build-on-target fallback |
| `Broken pipe` during SSH deploy | VPS overloaded, SSH timeout | Reduce parallelism; use artifact transfer instead of on-target build |
| `COPY failed` during on-target build | `docker-compose.build.yml` missing or wrong context | Ensure `docker-compose.build.yml` is committed with correct per-service contexts |
| RTPengine `exec: "--interface=...": no such file` | Dockerfile ENTRYPOINT/CMD misconfiguration | Verify `ENTRYPOINT ["rtpengine"]` + `CMD ["--foreground", "--log-stderr"]` |
| Postgres permission denied | Missing capabilities under `cap_drop: ALL` | Add `cap_add: [CHOWN, SETUID, SETGID, DAC_OVERRIDE]` |
| Load avg >100 after deploy | Multiple concurrent compose up processes | Kill stuck processes; wait for recovery; restart sequentially |

---

## CI/CD Pipeline (Feature 005)

### Workflow

The GitHub Actions workflow `.github/workflows/ci.yml` runs on every push to `main`/`master`:

| Job | Purpose | Duration |
|-----|---------|----------|
| `validate` | Docker Compose, OpenSIPS config, Nginx, Ansible, secrets | ~2 min |
| `build-opensips` | Build OpenSIPS Docker image | ~15 min |
| `build-ocp` | Build OCP theme image | ~5 min |
| `build-supporting` | Build Prometheus, Grafana, exporter, backup, ca-tool, anomaly-detector | ~10 min |
| `test-integration` | Start stack, health checks, pytest | ~5 min |
| `speckit-scan` | Brownfield, version-guard, memorylint checks | ~1 min |
| `security-scan` | Trivy vulnerability scan | ~3 min |
| `deploy` | Ansible deploy to staging/production (manual trigger) | ~5 min |

### Running CI Scans Locally

```bash
# Run the same checks as the CI speckit-scan job
bash scripts/ci-scan.sh

# Expected output:
# [brownfield] Checking for hardcoded :latest tags...
# PASS: No hardcoded :latest tags
# [version-guard] Checking for unpinned base images...
# PASS: Base image check complete
# [memorylint] Checking for container memory limits...
# PASS: Memory limits present on 24 services
# [security] Checking for committed secrets...
# PASS: No committed secrets
# === CI SCAN PASSED ===
```

### Manual Deploy

```bash
# Trigger deploy via GitHub CLI
gh workflow run ci.yml -f deploy_target=staging

# Or via GitHub web UI: Actions → TSiSIP CI/CD → Run workflow
```

### Troubleshooting CI Failures

| Failure | Cause | Fix |
|---------|-------|-----|
| `validate` fails | Invalid YAML or missing module | Check `docker compose config` output |
| `build-opensips` fails | Compilation error | Check Dockerfile for missing deps |
| `speckit-scan` fails | Hardcoded `:latest` or forbidden module | Run `bash scripts/ci-scan.sh` locally |
| `test-integration` fails | Container health check timeout | Increase sleep or check logs |
| `security-scan` fails | CVE in base image | Update base image digest or patch packages |

## Observability Stack Notes

### Prometheus Retention

Prometheus TSDB retention is configured explicitly in `docker-compose.yml` and `docker-compose.prod.yml` to prevent unbounded time-series growth. The current settings are:

- `--storage.tsdb.retention.time=30d`
- `--storage.tsdb.retention.size=10GB`

Adjust these values based on your storage capacity and metric retention requirements.

## TLS Certificate Rotation (Feature 015)

### Architecture

TSiSIP automates TLS certificate lifecycle management using Let's Encrypt (ACME v2) with Tailscale as the validation challenge solver. The `certbot` sidecar container obtains, renews, and writes certificates to a shared Docker volume mounted at `/certs/live/` on the OpenSIPS and OCP containers.

- **Automatic renewal**: Attempts daily at 02:00 UTC, 30 days before expiry
- **Staging vs production**: `--staging` flag uses Let's Encrypt staging CA to avoid rate limits during testing
- **Zero-downtime reload**: `tls-reload.sh` sends `SIGUSR1` to OpenSIPS and graceful Apache reload to OCP

### Checking Current Certificate Expiry

```bash
# On the OpenSIPS container
openssl x509 -in /certs/live/server.crt -noout -dates

# On the host via volume mount
docker compose exec opensips openssl x509 -in /certs/live/server.crt -noout -dates
```

The `notAfter` date is the expiry. If it is within 30 days, the next scheduled renewal run will attempt replacement.

### Manually Triggering Rotation

```bash
# Staging (test) rotation — does not count against LE rate limits
./scripts/cert-rotate.sh --staging

# Production rotation — uses live Let's Encrypt CA
./scripts/cert-rotate.sh --production
```

Monitor the `certbot` container logs during rotation:
```bash
docker compose logs -f certbot
```

### Reloading Certificates Without Restart

```bash
./scripts/tls-reload.sh
```

This script:
1. Verifies the new certificate chain with `openssl verify`
2. Sends `SIGUSR1` to the OpenSIPS process to trigger a TLS context reload
3. Gracefully reloads Apache in the OCP container to pick up the new `SSLCertificateFile`
4. Logs the reload event to the audit log

Verify reload succeeded:
```bash
# Check OpenSIPS TLS context
docker compose exec opensips opensips-cli -x mi get_statistics tls_opened_connections

# Check OCP TLS handshake
curl -vI https://tsiapp.io/TSiSIP/login.php 2>&1 | grep -E 'SSL|TLS|subject|issuer'
```

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `urn:ietf:params:acme:error:rateLimited` | Too many production requests | Switch to `--staging`; wait for the rate-limit window |
| `Challenge failed` | Tailscale ACL blocks HTTP-01 path | Verify `tailscale serve` is active and port 80 is open on the Tailscale interface |
| `CERTPATH_INVALID` | Intermediate chain missing | Ensure `fullchain.pem` (not just `cert.pem`) is copied to `server.crt` |
| OpenSIPS still serves old cert after reload | Process did not receive SIGUSR1 | Run `./scripts/tls-reload.sh` again and check `docker compose logs opensips` |
| OCP TLS handshake fails | Apache not reloaded | `docker compose exec ocp apachectl graceful` |

### Prometheus Alerts

| Alert | Meaning | Response |
|-------|---------|----------|
| `TSiSIPTLSCertExpiresSoon` | Certificate expires in ≤ 14 days | Check certbot logs; run `./scripts/cert-rotate.sh --production` manually |
| `TSiSIPTLSCertExpired` | Certificate has passed `notAfter` | Immediate rotation required; clients will reject connections |
| `TSiSIPTLSReloadFailed` | `tls-reload.sh` exit code ≠ 0 | Check script output; verify file permissions on `/certs/live/` |
| `CertbotContainerDown` | Certbot container not running | `docker compose up -d certbot`; inspect `docker compose logs certbot` |

## Audit Log & Compliance (Feature 016)

### Accessing the Audit Dashboard

1. Navigate to `https://tsiapp.io/TSiSIP/login.php`
2. Log in with an account that has the `admin` or `devops` role
3. From the OCP sidebar, select **Admin Tools → Audit Log & Compliance**

The dashboard displays:
- Event timeline with severity coloring
- Filterable columns: timestamp, actor, action, resource, outcome, source IP
- Hash chain verification status (green = intact, red = break detected)

### Exporting Audit Data

From the audit dashboard:
1. Click **Export** in the top-right corner
2. Choose format: **CSV** or **JSON**
3. Apply filters (date range, actor, action, resource type) before exporting
4. Click **Download** — the file is generated server-side and streamed via the browser

Bulk export via CLI:
```bash
# CSV export for last 7 days
docker compose exec postgres psql -U opensips -d opensips -c "
    COPY (
        SELECT event_time, actor, action, resource, outcome, source_ip, hash
        FROM audit_log
        WHERE event_time > NOW() - INTERVAL '7 days'
        ORDER BY event_time
    ) TO STDOUT WITH CSV HEADER;
" > audit_export_$(date +%Y%m%d).csv

# JSON export for compliance archive
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT json_agg(row_to_json(t))
    FROM (
        SELECT * FROM audit_log
        WHERE event_time > NOW() - INTERVAL '30 days'
        ORDER BY event_time
    ) t;
" > audit_archive_$(date +%Y%m%d).json
```

### Verifying Hash Chain Integrity

Each audit record includes a `prev_hash` field linking it to the prior record. The dashboard auto-verifies the chain on page load. To verify manually:

```bash
docker compose exec postgres psql -U opensips -d opensips -c "
    SELECT
        id,
        event_time,
        hash = digest(concat(prev_hash::text, actor, action, resource, event_time::text), 'sha256') AS integrity_ok
    FROM audit_log
    ORDER BY id
    LIMIT 20;
"
```

If any row returns `integrity_ok = f`, the chain has been tampered with or corrupted. Immediately:
1. Preserve the database snapshot
2. Check filesystem and database access logs
3. Escalate to the security officer

### Retention Policy

| Setting | Default | Where to Change |
|---------|---------|-----------------|
| Audit log retention | 90 days | Environment variable `AUDIT_RETENTION_DAYS` on the OCP container |
| Hash archive retention | 90 days | Same as above |

Change the retention period:
```bash
# Edit .env or docker-compose.yml
docker compose up -d ocp
```

Values > 365 days require explicit storage approval due to PostgreSQL table growth.

### Manual Retention Purge

```bash
# Purge records older than the current retention threshold
docker compose exec ocp php /var/www/html/web/cli/purge-audit-log.php

# Dry-run to see how many rows would be deleted
docker compose exec ocp php /var/www/html/web/cli/purge-audit-log.php --dry-run

# Force a specific retention window (overrides env var for this run)
docker compose exec ocp php /var/www/html/web/cli/purge-audit-log.php --days=30
```

The purge script:
1. Computes the cutoff date from `AUDIT_RETENTION_DAYS`
2. Deletes rows in batches of 1,000 to avoid long table locks
3. Runs `VACUUM` on the `audit_log` table
4. Writes a summary to stdout and to the audit log itself

## SIP Trunk Provider Management (Feature 017)

### Architecture

TSiSIP connects to upstream SIP trunk providers for PSTN ingress/egress. Each provider is modeled as a `sip_trunk_provider` record with associated DID mappings, health probes, and CPS throttling limits.

| Component | Table / File | Purpose |
|-----------|-------------|---------|
| Provider registry | `sip_trunk_providers` | Credentials, registration server, failover peer |
| DID mappings | `sip_trunk_did_mappings` | Public DID → tenant / dispatcher set |
| Health probes | Dispatcher set 100 + OCP status page | OPTIONS ping every 30s, state in `cachedb_local` |
| CPS throttling | `max_cps` column per provider | Calls-per-second ceiling via `rl_check` |

### Onboarding a New Trunk Provider

1. Log in to the OCP at `https://tsiapp.io/TSiSIP/login.php` with an `admin` account
2. Navigate to **Admin Tools → SIP Trunk Providers**
3. Click **Add Provider** and fill:
   - **Label**: Human-readable name (e.g., `VoIP Innovations`)
   - **Registrar**: SIP registrar URI (e.g., `sip:sip.voipinnovations.com:5060`)
   - **Username / Password**: Digest auth credentials (stored as HA1 hash)
   - **Transport**: `udp`, `tcp`, or `tls`
   - **Failover Peer**: Another provider label to route to if this one fails
   - **CPS Limit**: Maximum calls per second (default `10`)
4. Click **Save** — the provider is inserted into `sip_trunk_providers` with `enabled = true`
5. The `sync_trunk_providers_to_dispatcher` trigger auto-adds the provider to dispatcher set 100 for health probes
6. If `registration_required = true`, the `sync_trunk_registrations` trigger auto-populates `sip_trunk_registrations`
7. OpenSIPS will detect the new registration row on next `uac_registrant` timer cycle (max 60s)

Verify registration:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "SELECT name, state, last_register_succ FROM sip_trunk_registrations JOIN sip_trunk_providers p ON p.id = trunk_provider_id;"
```

### Adding DID Mappings

1. On the **SIP Trunk Providers** page, click **DIDs** next to the provider
2. Click **Add DID**:
   - **DID Number**: E.164 format (e.g., `+12025550123`)
   - **Tenant**: Select the tenant that owns this DID
   - **Destination**: Internal extension or SIP URI (e.g., `sip:reception@dev.tsisip.local`)
   - **Active Hours**: Optional time-of-day routing (default `00:00-23:59`)
3. Click **Save**

Bulk import via SQL:
```bash
docker compose exec postgres psql -U opensips -d opensips -c "
    INSERT INTO sip_trunk_did_mappings (did_number, tenant_id, trunk_provider_id, dispatcher_setid, description, enabled)
    SELECT '+12025550123', t.id, p.id, 1, 'Reception DID', true
    FROM sip_trunk_providers p, tenants t
    WHERE p.name = 'VoIP Innovations' AND t.sip_domain = 'dev.tsisip.local'
    ON CONFLICT (did_number, trunk_provider_id) DO NOTHING;
"
```

### Checking Trunk Health Status

**Via OCP:**
1. Go to **Admin Tools → SIP Trunk Providers**
2. The status column shows:
   - `Registered` — active registration, last OPTIONS 200 OK
   - `Unreachable` — no response to OPTIONS within timeout
   - `Throttled` — CPS limit hit, calls queued or rejected
   - `Failover Active` — traffic is routing to the failover peer

**Via Prometheus metrics:**
```bash
# Registration state (1 = registered, 0 = not registered)
curl -s http://localhost:9090/api/v1/query?query=tsisip_trunk_registration_state | jq .

# OPTIONS round-trip time
curl -s http://localhost:9090/api/v1/query?query=tsisip_trunk_options_rtt_ms | jq .

# Current CPS vs limit
curl -s http://localhost:9090/api/v1/query?query=tsisip_trunk_cps_current | jq .
```

### Interpreting Registration State

| State | Meaning | Operator Action |
|-------|---------|-----------------|
| `Registered` | UAC module has a valid registration with the provider | None |
| `Registration Pending` | REGISTER sent, no 200 OK yet | Check network path to provider registrar |
| `Authentication Failed` | 401/403 received on REGISTER | Verify credentials in OCP; re-check HA1 hash |
| `Expired` | Registration expired without renewal | Check `uac_reg` timer; verify provider SIP domain |
| `Disabled` | Provider manually disabled in OCP | Enable if intentional; otherwise investigate |

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Inbound PSTN calls fail with `404` | DID not mapped | Verify DID exists in `did_mappings` and `enabled = true` |
| Outbound calls dropped mid-call | Provider CPS throttling | Increase CPS limit in OCP or spread calls across multiple providers |
| `503 Service Unavailable` on outbound | Trunk down, failover not configured | Add a failover peer in provider settings; verify failover provider is healthy |
| High RTT on OPTIONS probe | Network latency or provider overload | Check `mtr` to provider IP; contact provider NOC if persistent |
| Registration flapping | NAT keepalive mismatch | Increase `uac_reg` `timer_expires` in OpenSIPS config; enable NAT pings |

### Grafana Dashboard

Open the `TSiSIP — SIP Trunk Providers` dashboard in Grafana for:
- Real-time registration state per provider
- CPS utilization vs limit (gauge + time series)
- OPTIONS RTT heatmap
- DID call volume by provider and tenant
- Failover event timeline

URL: `http://<grafana-host>/d/tsisip-sip-trunk-providers`

---

*Last Updated: 2026-05-20*

## Automated Runbooks (Feature 025)

Executable runbooks live in `scripts/runbook/` and produce JSON evidence
artifacts in `evidence/runbook/{timestamp}/`.

### PBX Failover

Mark a dispatcher destination as inactive and verify traffic shifts:

```bash
./scripts/runbook/failover-pbx.sh asterisk-pbx-1 1
```

Evidence artifact: `evidence/runbook/YYYYMMDD_HHMMSS_failover-{label}/evidence.json`

### TLS Certificate Rotation

Trigger certbot dry-run, then live rotation with automatic rollback on failure:

```bash
./scripts/runbook/rotate-tls-manual.sh tsiapp.io
```

Evidence artifact: `evidence/runbook/YYYYMMDD_HHMMSS_tls-rotate-{domain}/evidence.json`

### Scale Asterisk Backend

Add a new Asterisk backend to the dispatcher set and verify with health probe:

```bash
./scripts/runbook/scale-asterisk.sh 10.0.0.50 1 "asterisk-pbx-3"
```

Evidence artifact: `evidence/runbook/YYYYMMDD_HHMMSS_scale-{description}/evidence.json`

### Evidence Schema

Each evidence file follows this structure:

```json
{
  "runbook": "failover-pbx",
  "start_time": "2026-05-26T16:00:00Z",
  "end_time": "2026-05-26T16:00:15Z",
  "result": "success",
  "steps": [
    {"step": "identify_pbx", "status": "PASS", "detail": "Found destination id=3", "timestamp": "2026-05-26T16:00:01Z"},
    {"step": "mark_inactive", "status": "PASS", "detail": "State updated to 1", "timestamp": "2026-05-26T16:00:05Z"},
    {"step": "verify_shift", "status": "PASS", "detail": "2 active destinations remain", "timestamp": "2026-05-26T16:00:10Z"}
  ]
}
```

## Point-in-Time Recovery (PITR) — Feature 005/Stage 8

The `pitr-restore.sh` script performs logical backup restore to a temporary
database for validation or disaster-recovery rehearsal.

### PITR Restore Procedure

```bash
# 1. Verify which backup and WAL segments would be used (dry-run)
docker compose -f docker-compose.vps.yml exec backup \
  /usr/local/bin/pitr-restore.sh \
  --target 2026-05-20T14:30:00Z \
  --verify-only

# 2. Execute restore to a temporary database
docker compose -f docker-compose.vps.yml exec backup \
  /usr/local/bin/pitr-restore.sh \
  --target 2026-05-20T14:30:00Z \
  --temp-db opensips_pitr_20260520

# 3. Validate restored data
docker compose -f docker-compose.vps.yml exec postgres psql -U opensips \
  -d opensips_pitr_20260520 -c "SELECT COUNT(*) FROM subscriber;"

# 4. Drop temporary database when validation is complete
docker compose -f docker-compose.vps.yml exec postgres psql -U opensips \
  -d postgres -c "DROP DATABASE opensips_pitr_20260520;"
```

### Time-to-Recovery (TTR) Targets

| Database Size | Expected TTR | Notes |
|---|---|---|
| ≤ 1 GB | < 5 min | Logical restore + pg_restore on local SSD |
| 1–10 GB | < 15 min | Dominated by pg_restore index rebuild |
| 10–50 GB | < 30 min | Parallel restore recommended (`-j 4`) |

### Limitations

- `pitr-restore.sh` uses **logical backups** (`pg_dump` / `pg_restore`).
- True WAL replay to exact point-in-time requires physical replication
  (`pg_basebackup` + `pg_walreplay`), which is not the current baseline.
- For production DR, treat PITR as "nearest backup + manual replay" and
  document the acceptable RPO (Recovery Point Objective) in your SLA.

### Integration Tests

Run PITR validation locally:

```bash
python3 -m pytest tests/integration/test_backup_pitr.py -v
```

Expected: 4 passed (PITR-001 through PITR-004).
