# DevOps SIP Guide

## Primary Objectives

DevOps SIP operators keep the edge healthy, verify SIP routing, validate media relay posture, and maintain the production VPS-lite+PBX profile.

## Core Commands

```bash
cd /opt/tsisip

docker compose -f docker-compose.vps.yml ps
docker compose -f docker-compose.vps.yml logs -f opensips
docker compose -f docker-compose.vps.yml logs -f rtpengine
docker compose -f docker-compose.vps.yml logs -f asterisk-pbx-1
docker compose -f docker-compose.vps.yml logs -f asterisk-pbx-2
```

## SIP Validation

Validated internal signals:

- SIP `OPTIONS` over UDP/TCP returns `SIP/2.0 200 OK`.
- Unauthenticated `INVITE` returns authentication challenge.
- Authenticated `INVITE` reaches the Asterisk `from-opensips` context.

Use:

```bash
python3 scripts/sip-auth-probe.py --target <opensips-container-ip> --source <source-ip> --port <source-port>
```

## Public Edge Validation

Host checks:

```bash
sudo ss -lntup | grep -E '5060|5061|10000'
sudo ufw status verbose
docker compose -f docker-compose.vps.yml ps opensips rtpengine
```

External checks:

```bash
nmap -Pn -p 5060,5061 <public-ip>
```

If external scan shows `filtered` and host `tcpdump` receives no SYN packets, the block is upstream of the VPS.

## RTPengine

The VPS-lite profile publishes `10000-10999/udp`. The full production profile may use `10000-20000/udp`.

Operational notes:

- Keep RTPengine control on internal Docker networking only.
- Do not publish the ng-control socket.
- Use userspace fallback unless kernel module work is explicitly scheduled and validated.

## Backup and RPO

Backup metrics are local-only:

```bash
docker run --rm --network tsisip_metrics_host alpine wget -qO- http://backup:9101/metrics
```

Important metrics:

- `backup_rpo_lag_seconds`
- `backup_current_wal_info`
- `backup_rto_last_seconds`
- `backup_validation_status`
- `backup_success_total`

If `current_wal` equals `last_archived_wal`, WAL archiving is caught up and an idle database should show RPO `0`.
