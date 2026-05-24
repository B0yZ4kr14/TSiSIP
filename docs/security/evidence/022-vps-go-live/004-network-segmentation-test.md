# Network Segmentation Test Evidence (G7)

**Date**: 2026-05-23T23:06:00-03:00
**Tool**: docker compose config + nmap
**Target**: docker-compose.vps.yml (vps-lite profile)

---

## Port Exposure Audit

### Docker Compose Configured Ports

| Service | Published Port | Host IP | Protocol | Network | Status |
|---------|---------------|---------|----------|---------|--------|
| opensips | 5060 | 0.0.0.0 | udp/tcp | sip_edge | Expected — SIP signaling edge |
| rtpengine | 10000-20000 | 0.0.0.0 | udp | sip_edge | Expected — RTP media relay |
| ocp (nginx) | 80 | 127.0.0.1 | tcp | sip_edge | Loopback only — reverse proxy |
| certbot-exporter | 9101 | 127.0.0.1 | tcp | metrics_host | Loopback only — metrics |

### Services with ZERO Published Ports

| Service | Expected | Actual | Status |
|---------|----------|--------|--------|
| asterisk-pbx-1 | No public ports | None | PASS |
| asterisk-pbx-2 | No public ports | None | PASS |
| postgres | No public ports | None | PASS |
| backup | No public ports | None | PASS |
| certbot | No public ports | None | PASS |
| rtpengine (NG control) | No public ports | None | PASS |

### Host Port Scan (localhost)

```
PORT      STATE  SERVICE
5060/tcp  open   sip
5432/tcp  open   postgresql
8084/tcp  open   websnp
9090/tcp  closed zeus-admin
9093/tcp  closed copycat
22222/tcp closed easyengine
```

**Note**: Port 5432 (PostgreSQL) and 8084 (OCP direct) detected on localhost are from local development stack (docker-compose.yml), NOT from docker-compose.vps.yml. The vps-lite profile does not publish these ports.

---

## Network Isolation Verification

| Network | Members | External Access | Status |
|---------|---------|----------------|--------|
| sip_edge | opensips, rtpengine, ocp | Yes (public) | Correct |
| sip_internal | opensips, rtpengine, asterisk-pbx-1/2 | No | Internal only |
| db_internal | opensips, postgres | No | Internal only |
| metrics_host | certbot-exporter, prometheus | No | Internal only |

---

## Conclusion

**Status**: PASS — Zero public Asterisk/PostgreSQL ports in vps-lite profile.

AC6 verified: Port exposure audit confirms correct network segmentation.
