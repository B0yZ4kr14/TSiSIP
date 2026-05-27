# TSiSIP API Design Guide

## Principles

1. **RESTful**: Standard HTTP methods
2. **Stateless**: No server state
3. **Consistent**: Uniform responses
4. **Secure**: Authentication required
5. **Versioned**: URL versioning

## Endpoints

### Naming
- Nouns, not verbs
- Plural resources
- Kebab-case

### Examples
```
GET /health
POST /login
GET /api/v1/users
POST /api/v1/users
GET /api/v1/users/1
PUT /api/v1/users/1
DELETE /api/v1/users/1
```

## Requests

### Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer <token>
X-CSRF-Token: <token>
```

### Body
```json
{
    "username": "admin",
    "password": "admin123"
}
```

## Responses

### Success
```json
{
    "data": {...},
    "meta": {
        "page": 1,
        "per_page": 10,
        "total": 100
    }
}
```

### Error
```json
{
    "error": {
        "code": "INVALID_CREDENTIALS",
        "message": "Invalid username or password"
    }
}
```

### Status Codes
| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 500 | Internal Error |

## Authentication

### Session
- Cookie-based
- CSRF token
- Expires after inactivity

### API Key
- Header: `X-API-Key`
- Rate limited
- Scoped permissions

## Rate Limiting

### Headers
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640000000
```

### Limits
| Endpoint | Limit |
|----------|-------|
| Login | 5/min |
| API | 100/min |
| Export | 10/min |

## Pagination

### Query Params
```
?page=1&per_page=10
```

### Response
```json
{
    "data": [...],
    "links": {
        "first": "/api/v1/users?page=1",
        "last": "/api/v1/users?page=10",
        "next": "/api/v1/users?page=2",
        "prev": null
    }
}
```

## Filtering

### Query Params
```
?role=admin&status=active
```

### Operators
```
?created_at[gte]=2026-01-01
?created_at[lte]=2026-12-31
```

## Sorting

### Query Params
```
?sort=-created_at
```

### Format
- `+field`: Ascending
- `-field`: Descending

## Versioning

### URL
```
/api/v1/users
/api/v2/users
```

### Header
```
Accept: application/vnd.tsisip.v1+json
```

## Documentation

### OpenAPI
```yaml
openapi: 3.0.0
info:
  title: TSiSIP API
  version: 1.0.0
```

### Examples
See [API-EXAMPLES.md](API-EXAMPLES.md).

## Testing

### Tools
- curl
- Postman
- Insomnia
- httpie

### Scripts
```bash
bash tests/integration/test-ocp-all.sh
```

## Security

### Input Validation
- Type checking
- Length limits
- Sanitization
- SQL injection prevention

### Output Encoding
- JSON encoding
- HTML escaping
- XSS prevention

### CSRF Protection
- Token validation
- SameSite cookies
- Referrer check
