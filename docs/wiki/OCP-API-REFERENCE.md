# TSiSIP OCP API Reference

## Base URL
```
https://tsiapp.io/
```

## Authentication
All endpoints require a valid session cookie obtained via `/login.php`.

## Endpoints

### Health Check
```
GET /health.php
```
Returns system health status.

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2026-05-27T03:27:57+00:00",
    "version": "1.0.0",
    "checks": {
        "database": {"status": "ok"},
        "opensips": {"status": "ok", "uptime": 3600}
    }
}
```

### MI HTTP Proxy
```
POST /common/mi-http.php
```
Proxies OpenSIPS MI commands.

**Request:**
```json
{
    "method": "ds_list",
    "params": []
}
```

**Response:**
```json
{
    "Partitions": [...]
}
```

### Export Audit Log
```
GET /common/export-csv.php?table=ocp_audit_log&format=csv
```

### SSE Stream
```
GET /common/sse-stream.php?token=<csrf>
```
Server-Sent Events for real-time updates.

### Bookmark Toggle
```
POST /common/bookmark-toggle.php
```
**Request:**
```json
{
    "url": "gateway-health.php",
    "label": "Gateway Health",
    "icon": "star"
}
```

### Save Dashboard
```
POST /common/save-dashboard.php
```
**Request:**
```json
{
    "widgets": {
        "status": true,
        "management": false
    }
}
```

### Set Theme
```
GET /common/set-theme.php?theme=dark&csrf_token=<token>
```

### Set Language
```
GET /common/set-language.php?lang=es_ES&csrf_token=<token>
```

### Report Export
```
GET /common/export-report.php?type=logins&range=24h&format=csv
```

## Error Responses

### 400 Bad Request
```json
{"error": "Invalid input"}
```

### 403 Forbidden
```json
{"error": "Invalid token"}
```

### 405 Method Not Allowed
```json
{"error": "Method not allowed"}
```

### 503 Service Unavailable
```json
{"status": "degraded"}
```

## Rate Limits
- MI calls: 5 second cache TTL
- SSE: 1 message per 5 seconds
- Login: 5 attempts per 15 minutes

## CSRF Protection
All POST endpoints require a valid CSRF token in:
- Form field: `csrf_token`
- Header: `X-CSRF-Token`
- JSON body: `csrf_token`

## Session Security
- `cookie_secure`: HTTPS only
- `httponly`: JavaScript cannot access
- `samesite=Strict`: CSRF protection
- `use_strict_mode`: Prevent session fixation
