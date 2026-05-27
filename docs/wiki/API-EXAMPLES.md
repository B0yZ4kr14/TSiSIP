# TSiSIP API Examples

## Authentication

### Login
```bash
curl -X POST http://localhost/login.php \
  -d "username=admin&password=admin123" \
  -c cookies.txt
```

## Health

### Check Status
```bash
curl http://localhost/health.php
```

## MI Commands

### List Gateways
```bash
curl -X POST http://localhost/common/mi-http.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"method":"ds_list","params":[]}'
```

### Show Dialogs
```bash
curl -X POST http://localhost/common/mi-http.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"method":"dlg_list","params":[]}'
```

## Export

### CSV Export
```bash
curl "http://localhost/common/export-csv.php?table=ocp_audit_log&format=csv" \
  -b cookies.txt \
  -o audit.csv
```

### JSON Export
```bash
curl "http://localhost/common/export-json.php?table=ocp_audit_log&format=json" \
  -b cookies.txt \
  -o audit.json
```

## SSE

### Stream Events
```bash
curl "http://localhost/common/sse-stream.php?token=<csrf>" \
  -b cookies.txt
```

## Bookmarks

### Toggle Bookmark
```bash
curl -X POST http://localhost/common/bookmark-toggle.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"url":"gateway-health.php","label":"Gateway Health"}'
```

## Dashboard

### Save Layout
```bash
curl -X POST http://localhost/common/save-dashboard.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"widgets":{"status":true,"management":false}}'
```

## Reports

### Export Login Trends
```bash
curl "http://localhost/common/export-report.php?type=logins&range=24h&format=csv" \
  -b cookies.txt \
  -o logins.csv
```

## Python

### Using Requests
```python
import requests

session = requests.Session()
session.post('http://localhost/login.php', data={
    'username': 'admin',
    'password': 'admin123'
})

# Health
r = session.get('http://localhost/health.php')
print(r.json())

# MI
r = session.post('http://localhost/common/mi-http.php', json={
    'method': 'ds_list',
    'params': []
})
print(r.json())
```

## JavaScript

### Using Fetch
```javascript
// Login
await fetch('/login.php', {
    method: 'POST',
    body: new URLSearchParams({
        username: 'admin',
        password: 'admin123'
    })
});

// Health
const health = await fetch('/health.php').then(r => r.json());
console.log(health);
```

## cURL Tips

### Save Cookies
```bash
curl -c cookies.txt ...
```

### Use Cookies
```bash
curl -b cookies.txt ...
```

### Follow Redirects
```bash
curl -L ...
```

### Verbose
```bash
curl -v ...
```

### JSON Pretty Print
```bash
curl ... | python -m json.tool
```
