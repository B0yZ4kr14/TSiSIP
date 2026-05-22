# System Overview

## What TSiSIP Is

TSiSIP is a Docker-first SIP edge platform. Its SIP engine is based on OpenSIPS 3.6 LTS, and the system is designed to be the only public SIP signaling boundary for private Asterisk PBX backends.

The platform provides:

- SIP edge authentication and request handling through the TSiSIP SIP edge service.
- Media relay through RTPengine so PBX media addresses are not exposed.
- PostgreSQL-backed subscriber, routing, dispatcher, and operational data.
- Internal Asterisk PBX backends for validated SIP routing and failover.
- OCP-based control panel exposed through the existing HTTPS reverse proxy.
- Backup, WAL archiving, validation, and backup metrics in the VPS-lite profile.

## Production Profile

The active VPS profile is `docker-compose.vps.yml`.

| Service | Role | Exposure |
|---|---|---|
| `opensips` | SIP signaling edge | 5060/udp, 5060/tcp, 5061/tcp |
| `rtpengine` | RTP media relay | 10000-10999/udp |
| `postgres` | SIP database | internal only |
| `asterisk-pbx-1` | private PBX backend | internal only |
| `asterisk-pbx-2` | private PBX backend | internal only |
| `ocp` | control panel | loopback-only `127.0.0.1:8084`, proxied at `/TSiSIP/` |
| `backup` | backup, validation, metrics | 127.0.0.1:9101 |

## Readiness Summary

| Area | State |
|---|---|
| VPS stack health | Healthy on TSiAPP |
| Internal SIP routing | Validated through Asterisk |
| OCP HTTPS access | Validated through `https://tsiapp.io/TSiSIP/` |
| Backup/WAL manual path | Validated |
| Backup cron windows | Pending first observed 02:00/03:00/04:00 UTC cycle |
| PITR live restore | Pending |
| Offsite rclone/MinIO | Pending real credentials |
| External SIP 5060/5061 | Blocked upstream before host |

## Architecture Rules

- The TSiSIP SIP edge service is the only public SIP signaling endpoint.
- PostgreSQL is the only supported database.
- Asterisk and PostgreSQL must not publish host ports.
- RTPengine handles public RTP relay; backend media addresses must stay private.
- Runtime secrets must stay in Docker secrets or local secret files, never in git.
- Claims of readiness require command evidence.
