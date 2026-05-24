# Feature 022 — Performance & NFR Requirements Quality Checklist

**Purpose**: Validate the quality, clarity, and completeness of performance and non-functional requirements in spec.md and plan.md for Feature 022.

**Created**: 2026-05-23
**Feature**: 022 — VPS Go-Live Stabilization

---

## Performance Targets

- [ ] CHK001 - Are stack bring-up time requirements defined (target duration from docker compose up to healthy)? [Clarity, Gap]
- [ ] CHK002 - Is OCP response time threshold (5 seconds) defined under what load conditions? [Clarity, AC4]
- [ ] CHK003 - Are SIP transaction latency requirements defined (e.g., INVITE 407 response time)? [Coverage, Gap]
- [ ] CHK004 - Is database query performance baseline established (e.g., subscriber lookup < N ms)? [Completeness, Gap]

## Resource Limits

- [ ] CHK005 - Are CPU limits defined for each vps-lite service? [Completeness, Gap]
- [ ] CHK006 - Are memory limits defined for each vps-lite service? [Completeness, Gap]
- [ ] CHK007 - Are disk usage limits or alerts defined for PostgreSQL and backup volumes? [Coverage, Gap]
- [ ] CHK008 - Are resource requirements for 100 concurrent REGISTER requests quantified? [Clarity, Out of Scope]

## Scalability

- [ ] CHK009 - Is horizontal scaling explicitly excluded for vps-lite MVP? [Clarity, Out of Scope]
- [ ] CHK010 - Are vertical scaling limits defined (max CPU/RAM per service)? [Completeness, Gap]
- [ ] CHK011 - Is connection pooling configuration defined for PostgreSQL? [Coverage, Gap]

## Reliability

- [ ] CHK012 - Is healthcheck failure threshold (restart loop >5 in 60s) quantified with observation window? [Clarity, AC1]
- [ ] CHK013 - Are service dependency restart policies defined (restart: unless-stopped vs always)? [Completeness, Gap]
- [ ] CHK014 - Is graceful shutdown behavior defined for OpenSIPS (drain in-flight transactions)? [Coverage, Gap]

## Observability

- [ ] CHK015 - Are log retention requirements defined for container stdout/stderr? [Completeness, security_constitution §7]
- [ ] CHK016 - Are metric collection requirements defined (which metrics, scrape interval)? [Coverage, Gap]
- [ ] CHK017 - Are alerting thresholds defined for each service health metric? [Completeness, Gap]
- [ ] CHK018 - Is log aggregation/forwarding configuration defined? [Coverage, Gap]

## Load Testing

- [ ] CHK019 - Is load testing beyond 100 concurrent REGISTER explicitly excluded? [Clarity, Out of Scope]
- [ ] CHK020 - Are soak/stability test requirements defined (e.g., 24h continuous operation)? [Coverage, Gap]
