# Feature 004 Memory: Advanced Container Health Checks and Auto-Healing

## Current Scope
Multi-layer health probes (TCP, HTTP/JSON MI, SIP OPTIONS), Docker restart policies with exponential backoff, OpenSIPS dispatcher circuit breaker with half-open retry, and graceful degradation (488/480 SIP responses) for RTPengine and PostgreSQL failures.

## Relevant Decisions
- **Native Docker HEALTHCHECK** over Kubernetes liveness/readiness probes (Constitution-aligned Docker-first delivery).
- **Dispatcher module for circuit breaker**: Uses ds_ping_method=OPTIONS, ds_ping_interval=10, ds_probing_mode=1 with ds_set_state on threshold breach.
- **Half-open retry every 60s**: Prevents flapping backends from causing rapid circuit transitions.
- **Graceful SIP responses**: 488 Not Acceptable Here for RTPengine down; 480 Temporarily Unavailable for PostgreSQL down.

## Active Architecture Constraints
- Docker Compose V2 >= 2.20 required for restart_policy with delay and start_interval.
- Critical path containers (OpenSIPS, PostgreSQL) use restart: unless-stopped; supporting services use restart: on-failure.
- OpenSIPS, PostgreSQL, and RTPengine health checks are in scope; Asterisk health checks deferred.
- sanity module is forbidden in OpenSIPS 3.6 LTS.

## Accepted Deviations
- Asterisk health checks are deferred to a future Asterisk containerization feature.

## Relevant Security Constraints
- Probe credentials (e.g., MI authentication) stored as Docker secrets, never in image layers.
- All service and network names use lowercase snake_case.

## Related Historical Lessons
- Stagger start_period across containers (10s, 15s, 20s) to avoid false negatives during cold-start probe storms.
- Exponential backoff caps at 60s with max_attempts: 10 to prevent restart loops exhausting host resources.
- start_period of 120s for PostgreSQL prevents false positives from slow startup.

## Conflict Warnings
- Feature 003 observability integration adds Prometheus metrics for health state that depend on the same health check infrastructure.

## Retrieval Notes
- Search terms: healthcheck, auto-healing, circuit breaker, dispatcher probing, graceful degradation, restart policy, exponential backoff.
- Related features: 001 (OpenSIPS foundation), 003 (observability metrics), 005 (backup health).
