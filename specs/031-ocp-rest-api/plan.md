# Plan: OCP REST API

## Architecture

- Single entrypoint: web/api/index.php (router)
- Authentication middleware: check Authorization: Bearer header
- Rate limiting: in-memory counter per API key (Redis fallback future)
- Response format: JSON with consistent envelope

## Files

- web/api/index.php — Router and middleware
- web/api/common/auth.php — API key validation
- web/api/common/rate-limit.php — Rate limiting
- web/api/v1/status.php — System status
- web/api/v1/metrics.php — Metrics
- web/api/v1/users.php — User CRUD
- web/api/v1/audit.php — Audit log
- web/api-docs.php — Swagger UI
- db/init/09-api-keys-schema.sql — api_keys table
