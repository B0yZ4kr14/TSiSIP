# Feature 004 Memory Synthesis: Advanced Container Health Checks and Auto-Healing

## Current Scope
Multi-layer health probes, restart policies with backoff, dispatcher circuit breaker, and graceful degradation (488/480 SIP responses).

## Relevant Decisions
- Native Docker HEALTHCHECK over Kubernetes probes.
- Dispatcher module powers circuit breaker.
- Half-open retry every 60s prevents flapping.
- Graceful SIP responses for component failures.

## Active Architecture Constraints
- Docker Compose V2 >= 2.20 required.
- Critical containers: unless-stopped; others: on-failure.
- Asterisk health checks deferred.
- sanity module forbidden.

## Accepted Deviations
- Asterisk health checks deferred to future feature.

## Relevant Security Constraints
- Probe credentials via Docker secrets only.

## Related Historical Lessons
- Stagger start_period (10s, 15s, 20s) to avoid cold-start storms.
- Backoff caps at 60s to prevent restart loops.
- PostgreSQL needs 120s start_period.

## Conflict Warnings
- Feature 003 health metrics depend on this infrastructure.

## Retrieval Notes
- Keywords: healthcheck, circuit breaker, dispatcher probing, graceful degradation, restart policy.
- Related: 001, 003, 005.
