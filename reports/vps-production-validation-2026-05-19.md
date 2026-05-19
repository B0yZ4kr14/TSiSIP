# TSiSIP VPS Production Validation - 2026-05-19

## Result

TSiSIP is running on VPS TSiAPP from `/opt/tsisip` using the `docker-compose.vps.yml` production profile expanded with two internal Asterisk PBX backends.

## Runtime State

- `tsisip-postgres-1`: healthy
- `tsisip-rtpengine-1`: healthy
- `tsisip-opensips-1`: healthy
- `tsisip-ocp-1`: healthy
- `tsisip-backup-1`: healthy
- `tsisip-asterisk-pbx-1-1`: healthy
- `tsisip-asterisk-pbx-2-1`: healthy

## SIP Validation

- OPTIONS over UDP to OpenSIPS: `SIP/2.0 200 OK`
- OPTIONS over TCP to OpenSIPS: `SIP/2.0 200 OK`
- Unauthenticated INVITE over UDP/TCP: `SIP/2.0 401 Unauthorized`
- Authenticated INVITE using `scripts/sip-auth-probe.py`: `100 Giving it a try`, then `200 OK`
- Asterisk evidence: endpoint `1000@from-opensips` executed.

## Database State

- `dispatcher`: 2 active destinations in set 1
- `pbx_backends`: 2 linked rows
- `userblacklist`: schema present with `version.userblacklist=2`

## Public Edge

- HTTPS/OCP: `https://tsiapp.io/TSiSIP/` returns HTTP 302 to `/TSiSIP/login.php`
- VPS host firewall: UFW allows 5060/tcp, 5060/udp, 5061/tcp, and RTP range.
- External SIP scan: 5060/tcp and 5061/tcp show `filtered`.
- Packet capture during external scan: 0 SYN packets reached the VPS, so remaining SIP public exposure work is upstream of the host.

## Backup/WAL Validation

- WAL archiving enabled in Postgres; `.gz` WAL segments created under `/backup/wal/` after `pg_switch_wal()`.
- Backup pipeline validated end-to-end: `backup.sh` creates encrypted artifact in `/backup/daily/` with `.hmac`.
- Validation pipeline runs successfully (`validate.sh`) and emits Prometheus metrics.
- Backup metrics exporter reachable only on VPS loopback: `curl http://127.0.0.1:9101/metrics`.
- RPO monitor treats an idle database correctly: when `current_wal` equals `last_archived_wal`, `backup_rpo_lag_seconds` is `0`.
- Metrics now include `backup_current_wal_info{current_wal="...",last_archived_wal="..."}` so operators can distinguish idle databases from real archiver lag.

## Remaining Validation

- First automatic cron windows still need observation: backup at 02:00 UTC, purge at 03:00 UTC, validate at 04:00 UTC.
- PITR live restore and offsite replication are not yet proven because real rclone/MinIO credentials are environment-dependent.

## Validation Commands

- `bash scripts/ci-scan.sh`
- `cd deploy && bash validate.sh`
- `docker compose -f docker-compose.vps.yml config`
- `python3 scripts/sip-auth-probe.py --target 172.18.0.2 --source 172.18.0.1 --port 5096`
- `nmap -Pn -p 22,80,443,5060,5061 179.190.15.116 100.111.74.69`
- `docker compose -f docker-compose.vps.yml exec -T backup /usr/local/bin/backup.sh`
- `docker compose -f docker-compose.vps.yml exec -T backup /usr/local/bin/validate.sh`
- `docker compose -f docker-compose.vps.yml exec -T backup /usr/local/bin/purge.sh`
- `curl -fsS http://127.0.0.1:9101/metrics`
- `npx gitnexus status`
- GitNexus `query`, `context`, `impact`, and `detect_changes` against the backup/RPO path
