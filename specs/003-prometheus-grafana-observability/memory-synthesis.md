# Feature 003 Memory Synthesis: TSiSIP Observability Platform

## Current Scope
Prometheus + Grafana + Alertmanager for OpenSIPS, RTPengine, PostgreSQL metrics. Status: Partial.

## Relevant Decisions
- Python exporter sidecar polls MI and converts to Prometheus format.
- 10s scrape cache TTL prevents MI overload.
- Dashboard JSON as code, version controlled.
- 30-day high-res retention; long-term deferred.

## Active Architecture Constraints
- Services on `db_internal` only; no host ports.
- Cardinality limit: 10,000 series per metric family.

## Accepted Deviations
- Long-term retention not yet implemented.
- Log aggregation and distributed tracing out of scope.

## Relevant Security Constraints
- Metrics endpoints internal-only.
- Webhook alerts; final channel operator-configured.

## Related Historical Lessons
- >5ms scrape latency requires async buffering.
- 2-minute sustained breach rules reduce alert fatigue.
- Reuse Feature 002 `theme.json` for dashboard colors.

## Conflict Warnings
- Depends on Feature 001 MI interface and Feature 002 i18n.

## Retrieval Notes
- Keywords: observability, Prometheus, Grafana, exporter, metrics, alerting.
- Related: 001, 002, 004, 005.
