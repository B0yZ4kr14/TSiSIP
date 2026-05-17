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
