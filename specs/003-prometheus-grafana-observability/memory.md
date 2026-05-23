# Feature 003 Memory: TSiSIP Observability Platform with Prometheus and Grafana

## Current Scope
Real-time observability infrastructure exposing OpenSIPS, RTPengine, PostgreSQL, and host-level metrics through Prometheus time-series collection, Grafana dashboards, and Alertmanager notifications. Status: Partial.

## Relevant Decisions
- **Custom OpenSIPS exporter sidecar**: A lightweight Python exporter polls OpenSIPS MI and transforms output to Prometheus exposition format, rather than relying on mi_http directly as a scrape endpoint.
- **Scrape caching (10s TTL)**: Prevents MI interface overload from frequent Prometheus scrapes.
- **Dashboard JSON as code**: Grafana dashboards are version-controlled JSON files provisioned at container startup.
- **30-day high-resolution retention**: 15s scrape interval with 10GB storage cap; long-term downsampled retention deferred.

## Active Architecture Constraints
- Prometheus, Grafana, and Alertmanager attach only to db_internal network (no host-published ports).
- Metric endpoint binds internally; no authentication required for /metrics.
- Cardinality limit: no single metric family exceeds 10,000 time series.
- OpenSIPS MI interface must be accessible internally for exporter scraping.

## Accepted Deviations
- Long-term retention (1 year downsampled) is planned but not implemented.
- Log aggregation (Loki/ELK) and distributed tracing (Jaeger/Zipkin) are out of scope.

## Relevant Security Constraints
- Metrics endpoints are internal-only; no exposure to sip_edge or host network.
- Alert notifications delivered via generic webhook; final channel (Slack/email/PagerDuty) is operator-configured in Alertmanager.

## Related Historical Lessons
- If Prometheus scrape adds >5ms latency per SIP message, pivot to asynchronous metric buffering (documented falsification hypothesis).
- Multi-condition alert rules with 2-minute sustained breach reduce alert fatigue.
- Dashboard color palette should reuse theme.json from Feature 002 for visual consistency.

## Conflict Warnings
- Depends on Feature 001 (OpenSIPS MI interface must be available for metric extraction).
- Feature 002 provides the role-aware UI framework; dashboard i18n (EN/ES/PT) should align with OCP locale infrastructure.

## Retrieval Notes
- Search terms: observability, Prometheus, Grafana, Alertmanager, OpenSIPS exporter, metrics, alerting, cardinality.
- Related features: 001 (MI interface), 002 (theme/i18n), 004 (health metrics), 005 (backup SLA metrics).
