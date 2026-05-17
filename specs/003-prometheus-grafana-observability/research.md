# Research: TSiSIP Observability Platform with Prometheus and Grafana

## Decision: Prometheus Exposition Format vs StatsD

**Decision**: Use Prometheus native exposition format via custom exporter sidecar.

**Rationale**:
- Prometheus is the de facto standard for cloud-native monitoring
- Native scraping integrates with Grafana out-of-the-box
- No additional aggregation layer needed
- OpenSIPS MI JSON is easily transformable to Prometheus format

**Alternatives considered**:
- StatsD + Graphite: older stack, less Grafana integration
- InfluxDB line protocol: good but requires InfluxDB
- OpenTelemetry: overkill for current scope

## Decision: Exporter Sidecar vs In-Process Metrics

**Decision**: Use Python sidecar container that polls MI interface.

**Rationale**:
- No modification to OpenSIPS core needed
- Isolated failure domain (exporter crash doesn't affect SIP)
- Easy to update/restart independently
- Python ecosystem has excellent Prometheus client library

**Alternatives considered**:
- OpenSIPS stats module: limited metric types
- C module in OpenSIPS: complex build process
- SNMP: legacy protocol, limited ecosystem

## Decision: Retention Policy

**Decision**: 30 days high-resolution (15s), 10GB max size.

**Rationale**:
- 30 days covers incident investigation window
- 10GB is conservative for projected load (~1000 concurrent calls)
- Downsampled data beyond 30 days can be archived to S3
- Aligns with PostgreSQL backup retention (Feature 005)

## Decision: Dashboard Provisioning Approach

**Decision**: Grafana provisioning with JSON-as-code.

**Rationale**:
- Dashboards are version controlled
- Consistent across environments
- No manual UI configuration needed
- i18n can be managed via JSON files

**Alternatives considered**:
- Manual dashboard creation: not reproducible
- Terraform Grafana provider: overkill for current scope
- Grafana API scripting: more complex than provisioning

## Decision: Alert Rule Thresholds

**Decision**: 2-minute sustained breach for critical, 5-minute for warnings.

**Rationale**:
- 2 minutes filters transient spikes
- 5 minutes for resource utilization prevents flapping
- Aligns with SIP retransmission timeouts
- NOC operators need time to assess before alert fires

## Falsification Hypotheses

1. **Hypothesis**: Prometheus scrape adds >5ms latency per SIP message.
   **Test**: Measure OpenSIPS throughput with exporter enabled.
   **Mitigation**: If true, increase cache TTL or use async metric collection.

2. **Hypothesis**: Dashboard JSON becomes unmaintainable.
   **Test**: Track time to modify dashboard after spec change.
   **Mitigation**: If >30 min, migrate to dashboard generation tool.

3. **Hypothesis**: Alert fatigue from noisy thresholds.
   **Test**: Count alerts per week in production.
   **Mitigation**: If >50/week, tune thresholds or add inhibition rules.
