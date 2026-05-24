# ADR-023: Subscriber Proxy Architecture Decision

**Date**: 2026-05-24
**Status**: APPROVED (Revised)
**Deciders**: speckit-implement (automated), Architecture Guard

---

## Context

Feature 023 addresses ARCH-PRE-001: OCP (web/subscribers.php) performs direct INSERT/UPDATE/DELETE on the subscriber table, violating the Control Plane to Database boundary in the architecture constitution.

Two approaches were evaluated:
- **Option A**: OpenSIPS MI commands (subscriber_create, subscriber_update, subscriber_delete) using the sql_query module
- **Option B**: PHP REST microservice (docker/admin-api/) with dedicated PDO connection

## Decision

**Option B (PHP REST Microservice) is selected.**

### Rationale for Revision

Option A (OpenSIPS MI commands) was initially preferred for constitution alignment and zero new containers. However, technical evaluation revealed that OpenSIPS 3.6 LTS does not expose a practical mechanism for custom MI commands with parameterized input validation without module development (C coding) or complex scripting layers. The available `mi_http` module exposes built-in commands but does not support safe parameterized SQL queries via MI, making it impossible to implement HA1 validation and prepared statements at the proxy layer.

Option B provides:
- **Testability**: PHP unit and integration tests are straightforward
- **Input validation**: Native PHP validation with prepared PDO statements
- **Audit logging**: Direct database access for auth_audit_log
- **Rate limiting**: File-based counters with configurable thresholds
- **Operational simplicity**: Single-purpose container with minimal attack surface

## Consequences

### Positive

- **Secure by design**: Prepared PDO statements eliminate SQL injection; hex validation enforces HA1 format
- **Independent scaling**: Admin API can be scaled separately from OCP if needed
- **Clear boundary**: OCP (Control Plane) calls Admin API (Proxy Layer) which writes to Database
- **Testability**: Can test proxy layer independently with curl/PHPUnit

### Negative

- **New container**: Adds one Docker image (`tsisip/admin-api`) to the stack
- **New attack surface**: Internal HTTP endpoint on port 8080 (mitigated by internal network + service secret)
- **Supply chain**: New base image (`php:8.2-apache`) requires scanning and pinning

### Mitigations for Negative Consequences

- Image based on official `php:8.2-apache` with minimal extensions (pdo_pgsql only)
- Port 8080 is NOT published in docker-compose; accessible only via `sip_internal` network
- Service secret (`proxy_api_secret`) mounted as Docker secret
- Rate limiting prevents abuse even if secret is compromised

## Rejected Option

**Option A (OpenSIPS MI Command)** was rejected because OpenSIPS 3.6 LTS does not provide a viable mechanism for custom MI commands with parameterized SQL and input validation without custom module development.

## Compliance

- Aligns with Architecture Constitution v1.2.0 (Layer Boundaries)
- Resolves ARCH-PRE-001
- No rejected patterns introduced (no db_mysql, no bare-metal install)
