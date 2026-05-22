# Network Binding Decisions — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-NBD-001  
**Date**: 2026-05-19  
**Applies to**: TSiAPP VPS deployment (all compose profiles)  
**Review cycle**: 90 days  

---

## 1. Backup Metrics Exporter Binding

### Decision

The backup metrics exporter (`certbot-exporter` and `backup` services) uses **context-appropriate binding**:

| Profile | Binding | Rationale |
|---|---|---|
| `docker-compose.yml` (full) | Container-only (`expose: ["9101"]`) | Prometheus is containerized on the same Docker network (`db_internal`). No host-published port needed. |
| `docker-compose.prod.yml` (production) | Container-only (`expose: ["9101"]`) | Same as full profile. |
| `docker-compose.vps.yml` (VPS-lite) | Container-only (`expose: ["9101"]`) with `METRICS_ADDR=0.0.0.0` | Host-level Prometheus agent (if present) scrapes via Docker bridge IP. The container binds to all interfaces internally, but no host port is published. |

### Rationale

- **Security**: No host-published port means the metrics endpoint is unreachable from the public internet. UFW default-deny and fail2ban provide additional network-layer protection.
- **Operational**: Containerized Prometheus on `db_internal` can scrape the exporter directly. For host-level monitoring (e.g., node_exporter co-located with Docker), the bridge network IP is sufficient.
- **Consistency**: All three compose profiles avoid `ports:` for the backup/certbot exporter. The only variation is `METRICS_ADDR`, which is an internal container binding decision.

### Accepted Risk

- If a host-level Prometheus is used without Docker network access, scraping the backup exporter requires explicit Docker network configuration. This is documented in `deploy/README.md`.

---

## 2. RTPengine Control Socket Binding

### Decision

RTPengine's control socket (`--listen-ng`) binds to `${RTPENGINE_INTERNAL_IP}:22222`, not `0.0.0.0`.

### Rationale

- The control socket accepts commands from OpenSIPS only (via `rtpengine` module MI interface).
- Binding to a specific internal address prevents accidental exposure if the `sip_internal` network configuration drifts.
- The `sip_internal` network is marked `internal: true`, providing defense in depth.

---

## 3. Anomaly Detector Binding

### Decision

The anomaly detector binds to `127.0.0.1:8080` on the host in the full profile.

### Rationale

- The anomaly detector exposes a health endpoint and metrics on port 8080.
- Binding to loopback ensures only local processes (e.g., Prometheus scraping via host network, or localhost curl) can access it.
- No sensitive data is exposed on this endpoint (only health status and anomaly counts).

---

## 4. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Author | Security Governance | 2026-05-19 | Approved |
| Reviewer | Architecture Review | 2026-05-19 | Approved |
