# Runbooks and Troubleshooting

## Stack Health

```bash
ssh ssh-tsiapp
cd /opt/tsisip
docker compose -f docker-compose.vps.yml ps
```

If any service is unhealthy:

```bash
docker compose -f docker-compose.vps.yml logs --tail=200 <service>
docker compose -f docker-compose.vps.yml up -d --no-deps --force-recreate <service>
```

## OCP 502 or Login Failure

```bash
docker compose -f docker-compose.vps.yml ps ocp
docker compose -f docker-compose.vps.yml logs --tail=200 ocp
curl -Ik https://127.0.0.1/TSiSIP/login.php
curl -I https://tsiapp.io/TSiSIP/
```

## SIP External Filtered

Symptoms:

- `nmap -Pn -p 5060,5061 <public-ip>` shows `filtered`.
- The TSiSIP SIP edge service is listening locally.
- UFW allows 5060/5061.
- `tcpdump` sees no inbound SYN during the external scan.

Conclusion: the block is upstream of the VPS host.

Actions:

```bash
sudo ufw status verbose
sudo ss -lntup | grep -E '5060|5061'
sudo tcpdump -ni any 'port 5060 or port 5061'
```

Then update provider/NAT/edge ACLs.

## Backup Failure

```bash
docker compose -f docker-compose.vps.yml logs --tail=200 backup
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/backup.sh
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/validate.sh
```

Check:

- `secrets/db_password` exists and is non-empty.
- `secrets/backup_encryption_key` exists and is non-empty.
- `/backup/daily/latest` points to a real artifact.
- `.hmac` exists beside encrypted artifacts.

## RPO Alert

```bash
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/rpo-monitor.sh
docker run --rm --network tsisip_metrics_host alpine wget -qO- http://backup:9101/metrics | grep -E 'backup_rpo|backup_current_wal'
```

If `current_wal` equals `last_archived_wal`, the archive is caught up and RPO should be `0`.

## WAL Archive Permission Error

Symptoms in Postgres logs:

```text
cannot create /backup/wal/... Permission denied
```

Fix:

```bash
docker compose -f docker-compose.vps.yml exec -T --user root postgres \
  sh -lc 'mkdir -p /backup/wal && chown -R postgres:postgres /backup/wal && chmod 750 /backup/wal'
```

Then force a WAL switch:

```bash
docker compose -f docker-compose.vps.yml exec postgres \
  psql -U opensips -d opensips -c 'SELECT pg_switch_wal();'
```
