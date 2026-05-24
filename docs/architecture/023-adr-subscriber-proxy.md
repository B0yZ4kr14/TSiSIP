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

## Proxy API Contract

This section documents the JSON request/response contract between the OCP and the Admin API proxy.

### Authentication

All requests must include:
- Header: `X-Proxy-Secret: <service_secret>` (read from Docker secret mount)
- Method: `POST`
- Content-Type: `application/json`

### Endpoint

```
POST http://admin-api:8080/index.php
```

### Request Schema

```json
{
  "action": "create|update|delete",
  "data": { }
}
```

#### Create Payload

```json
{
  "username": "string (max 64)",
  "domain": "string (max 253)",
  "ha1": "string (32 hex chars)",
  "ha1_sha256": "string (64 hex chars)",
  "ha1_sha512t256": "string (64 hex chars)",
  "email": "string (max 255, optional)",
  "tenant_id": "UUIDv4 string",
  "enabled": "boolean (default: true)"
}
```

#### Update Payload

```json
{
  "id": "integer",
  "username": "string (max 64)",
  "domain": "string (max 253)",
  "ha1": "string (32 hex chars, optional)",
  "ha1_sha256": "string (64 hex chars, optional)",
  "ha1_sha512t256": "string (64 hex chars, optional)",
  "email": "string (max 255, optional)",
  "tenant_id": "UUIDv4 string",
  "enabled": "boolean"
}
```

#### Delete Payload

```json
{
  "id": "integer"
}
```

### Response Schema

**Success (HTTP 200):**
```json
{"success": true}
```

**Error (HTTP 400/403/429/500):**
```json
{
  "success": false,
  "error": "Human-readable error message",
  "errors": ["Field-specific errors (optional)"]
}
```

### Validation Rules

| Field | Rule | Failure Response |
|---|---|---|
| `username` | Alphanumeric plus dot/underscore/hyphen, max 64 chars | 400 |
| `domain` | Valid FQDN or IP, max 253 chars | 400 |
| `ha1` | 32 hex chars (MD5) | 400 |
| `ha1_sha256` | 64 hex chars | 400 |
| `ha1_sha512t256` | 64 hex chars | 400 |
| `password` | Must be absent or empty | 400 (plaintext rejection) |
| `tenant_id` | UUIDv4 format | 400 |
| `enabled` | Boolean | 400 |

### Rate Limits

| Action | Limit | Window |
|---|---|---|
| `create` | 10 requests | 60 seconds per source IP |
| `update` | 30 requests | 60 seconds per source IP |
| `delete` | 10 requests | 60 seconds per source IP |

Exceeding the limit returns HTTP 429.

### Error Handling in OCP

The `callSubscriberProxy()` helper translates proxy errors into user-friendly messages:
- cURL failure: "Subscriber service temporarily unavailable. Please try again later."
- HTTP 429: "Too many subscriber changes. Please wait a moment and try again."
- HTTP 403: "Access denied to subscriber service."
- HTTP non-200: "Subscriber operation failed. Please contact support."
- Invalid JSON: "Invalid response from subscriber service."

No stack traces, internal paths, or raw error messages are exposed to the end user.
