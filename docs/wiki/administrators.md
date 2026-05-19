# Administrator Guide

## Access Surfaces

| Surface | Access Path | Notes |
|---|---|---|
| VPS SSH | `ssh-tsiapp` | Canonical host alias on the workstation |
| OCP | `https://tsiapp.io/TSiSIP/` | Public HTTPS path through Nginx |
| Backup metrics | `http://127.0.0.1:9101/metrics` | VPS-local only |
| SIP | 5060/udp, 5060/tcp, 5061/tcp | Host-ready; external exposure still blocked upstream |

## Routine Health Check

```bash
ssh ssh-tsiapp
cd /opt/tsisip

docker compose -f docker-compose.vps.yml ps
curl -I https://tsiapp.io/TSiSIP/
curl -fsS http://127.0.0.1:9101/metrics | head
```

## Backup Administration

Manual backup:

```bash
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/backup.sh
```

Validate latest backup:

```bash
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/validate.sh
```

Purge retention:

```bash
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/purge.sh
```

Pending administrative gate:

- Observe the first automatic cron cycle after deployment: backup at 02:00 UTC, purge at 03:00 UTC, validation at 04:00 UTC.

## Secrets

Required secret files are under `/opt/tsisip/secrets/` on the VPS.

Do not print secret values. Validate only existence, size, mode, and whether the file is non-empty.

## Rollback

```bash
cd /opt/tsisip
docker compose -f docker-compose.vps.yml ps
docker compose -f docker-compose.vps.yml logs --tail=200 <service>
docker compose -f docker-compose.vps.yml up -d --no-deps --force-recreate <service>
```

For broad rollback, stop the stack first:

```bash
docker compose -f docker-compose.vps.yml down
```
