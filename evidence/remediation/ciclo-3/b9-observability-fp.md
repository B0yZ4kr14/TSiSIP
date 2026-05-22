# Evidence: B9 — Observability Services Finding

## Finding
- **ID**: B9
- **Severity**: MEDIUM
- **Category**: Config Rot
- **File**: `docker-compose.prod.yml`
- **Claim**: Prometheus/Grafana/Alertmanager services present but disabled by comment

## Investigation Result
**FALSE POSITIVE** — No commented-out observability services exist.

| Compose File | Prometheus | Grafana | Alertmanager | Anomaly Detector | Exporter |
|---|---|---|---|---|---|
| `docker-compose.prod.yml` | ACTIVE | ACTIVE | ACTIVE | ACTIVE | ACTIVE |
| `docker-compose.yml` | ACTIVE | ACTIVE | ACTIVE | ACTIVE | ACTIVE |
| `docker-compose.vps.yml` | ABSENT | ABSENT | ABSENT | ABSENT | ABSENT |

### Key Observations
- `docker-compose.prod.yml`: All 5 observability services are fully configured with healthchecks, resource limits, and restart policies
- `docker-compose.vps.yml`: Services are intentionally omitted (not commented) for the VPS-lite profile (~4GB RAM)
- Header comment in VPS file correctly documents this design decision

## Conclusion
No remediation required. The brownfield scan misclassified the VPS-lite profile header comment as evidence of commented services.
