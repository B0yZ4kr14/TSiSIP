# Feature 008 — Deployment Data Model

## Deployment Entities

### VPS (TSiAPP)
- **Attributes**: host, OS (Ubuntu 24.04), public IP, SSH key (Ed25519), resource limits (~4 GB RAM)
- **Relationships**: hosts all Docker services; targeted by Ansible playbooks

### Services (vps-lite+PBX profile)
| Service | Network Membership | Published Ports | Memory Limit |
|---|---|---|---|
| `postgres` | `db_internal`, `metrics_host` | — | 512 MB |
| `rtpengine` | `sip_edge`, `sip_internal` | `10000-10999/udp` | 256 MB |
| `opensips` | `sip_edge`, `sip_internal`, `db_internal` | `5060/udp+tcp`, `5061/tcp` | 256 MB |
| `ocp` | `sip_internal`, `db_internal`, `metrics_host` | `127.0.0.1:8084/tcp` | 256 MB |
| `asterisk-pbx-1` | `sip_internal` | — (internal only) | 768 MB |
| `asterisk-pbx-2` | `sip_internal` | — (internal only) | 768 MB |
| `backup` | `db_internal`, `metrics_host` | `127.0.0.1:9101/tcp` | 128 MB |

> **Note**: Services disabled in vps-lite profile: `prometheus`, `grafana`, `alertmanager`, `opensips-exporter`, `anomaly-detector`. These are deferred to Phase 2.

## Secrets Model

### Secret Inventory
| Secret | Consumers | Storage | Injection Method |
|---|---|---|---|
| `db_password` | postgres, opensips, ocp, backup | `secrets/db_password` | Docker secret mount |
| `auth_secret` | opensips | `secrets/auth_secret` | Docker secret mount |
| `topology_secret` | opensips | `secrets/topology_secret` | Docker secret mount |
| `backup_encryption_key` | backup | `secrets/backup_encryption_key` | Docker secret mount |
| `ca.crt` | opensips | `secrets/ca.crt` | Docker secret mount |
| `server.crt` | opensips | `secrets/server.crt` | Docker secret mount |
| `server.key` | opensips | `secrets/server.key` | Docker secret mount |
| `crl.pem` | opensips | `secrets/crl.pem` | Docker secret mount |

### Secret Management Rules
1. **No secrets in version control**: `secrets/` and `.env*` are gitignored.
2. **Runtime injection only**: Secrets are mounted via Docker Compose `secrets:` stanza or environment-templated configs.
3. **Deploy vs. operational scope separation**:
   - *Deploy secrets*: GitHub token, SSH key, VPS host/user — used by Ansible and bootstrap scripts.
   - *Operational secrets*: TSiHomeLab vault key, backup encryption key — used by running services.
4. **Generation**: `auth_secret` must be exactly 32 bytes. All secrets are generated during bootstrap via `openssl rand`.

## Network Topology

### Docker Networks

```
Internet
   |
   | 5060/udp+tcp, 5061/tcp
   v
+------------------+
|   sip_edge       |  <-- Public SIP / RTP ingress
| (opensips,       |
|  rtpengine)      |
+--------+---------+
         |
         | sip_internal (internal: true)
         v
+------------------+
|  sip_internal    |  <-- SIP forwarding, RTPengine control
| (opensips,       |
|  rtpengine,      |
|  asterisk-pbx-*) |
+--------+---------+
         |
         | db_internal (internal: true)
         v
+------------------+
|   db_internal    |  <-- Database access only
| (opensips,       |
|  ocp, backup,    |
|  postgres)       |
+------------------+
```

| Network | Driver | Internal | Purpose |
|---|---|---|---|
| `sip_edge` | bridge | no | Public SIP signaling and RTP media ingress |
| `sip_internal` | bridge | yes | Internal SIP relay and RTPengine ng-control |
| `db_internal` | bridge | yes | PostgreSQL and service DB access |
| `metrics_host` | bridge | no | Loopback-only metrics exposure (backup exporter) |

**Isolation enforcement**:
- Asterisk services have **no** `ports:` stanza and attach only to `sip_internal`.
- PostgreSQL has **no** `ports:` stanza and attaches only to `db_internal`.
- RTPengine control socket (`--listen-ng`) binds to `${RTPENGINE_INTERNAL_IP}:22222`, never `0.0.0.0`.

## Service Dependency Graph

```
postgres
  ├── opensips (condition: service_healthy)
  ├── ocp      (condition: service_healthy)
  ├── backup   (condition: service_healthy)
  └── rtpengine (started before opensips)

rtpengine
  └── opensips (condition: service_started)

opensips
  └── asterisk-pbx-* (no explicit depends_on; runtime via dispatcher)
```

## Environment Variable Contract

### Required Variables
| Variable | Used By | Description |
|---|---|---|
| `TSISIP_IMAGE_TAG` | All services | Image tag (e.g., `latest` or git short-SHA) |
| `OPENSIPS_LISTEN_IP` | opensips | Bind address for SIP sockets |
| `HOST_PUBLIC_IP` | opensips, rtpengine | Advertised public IP in SIP headers and RTP |
| `RTPENGINE_PRIVATE_IP` | rtpengine | Private interface for RTP |
| `RTPENGINE_INTERNAL_IP` | rtpengine | Internal Docker IP for ng-control socket |
| `RTPENGINE_HOST` | opensips | Hostname for RTPengine control |
| `DB_HOST` | opensips, ocp, backup | PostgreSQL hostname |
| `DB_NAME` | opensips, ocp, backup | Database name |
| `DB_USER` | opensips, ocp, backup | Database user |

### Optional Variables
| Variable | Default | Description |
|---|---|---|
| `TOPOLOGY_SECRET` | — | Topology hiding secret (32+ chars recommended) |
| `GRAFANA_ROOT_URL` | `http://localhost:3000` | Grafana external URL |
| `ALERTMANAGER_WEBHOOK_URL` | `http://localhost:5000/alerts` | Alertmanager webhook |

### Variable Precedence
1. `.env` file (loaded by Docker Compose)
2. Shell environment (overrides `.env`)
3. Docker Compose defaults (`${VAR:-default}`)
4. Secret file mounts (for credentials)

See [`.env.example`](../../.env.example) for the canonical template.
