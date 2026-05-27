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

### Generic MI Action
```
POST /common/mi-action.php
```
Executes whitelisted MI commands with structured parameters and responses.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cmd` | string | Yes | Whitelisted MI command name |
| `params` | JSON array | No | Ordered command parameters |
| `csrf_token` | string | Yes | Valid CSRF token |

**Request:**
```json
{
    "cmd": "dlg_end_dlg",
    "params": ["abc123", "def456"],
    "csrf_token": "a1b2c3d4e5f6"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "result": "OK"
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Command not whitelisted or insufficient permissions"
}
```

**Role Requirement:**
- Read-only commands: All authenticated users
- Mutation commands (e.g., `dlg_end_dlg`, `reload` variants): **devops** or higher

### Whitelisted MI Commands
The following commands are available through `mi-action.php`:

| Category | Commands |
|----------|----------|
| System | `version`, `get_statistics`, `list_modules`, `mem_pkg_dump`, `mem_shm_dump`, `list_blacklists`, `list_hash_table`, `get_hash_item`, `get_profile_size`, `ul_dump` |
| Dialog | `dlg_list`, `dlg_list_ctx`, `dlg_end_dlg`, `dlg_db_sync` |
| Gateway | `ds_list`, `ds_reload`, `dr_status`, `dr_reload`, `lb_list`, `lb_reload`, `clusterer_list`, `clusterer_reload` |
| RTPengine | `rtpengine_show`, `rtpengine_reload` |
| Subscriber | `ul_show_contact`, `uac_reg_refresh`, `uac_reg_enable`, `uac_reg_disable` |
| NAT | `nh_show_active`, `nh_keepalive`, `nh_delip` |
| Topology | `th_show_info`, `th_show_locks` |
| Rate Limiting | `pike_list`, `rl_get_pipes` |
| Presence | `pres_refresh_watchers`, `list_pua` |
| TLS | `tls_list`, `tls_reload` |
| Call Center | `cc_agent_login`, `cc_agent_list` |
| UAC | `uac_reg_list`, `uac_reg_reload` |

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
