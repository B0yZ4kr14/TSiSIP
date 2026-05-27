# TSiSIP Testing Guide

## Types

### Unit Tests
- PHP functions
- Helpers
- Utilities

### Integration Tests
- End-to-end flows
- API endpoints
- Database operations

### Manual Tests
- UI/UX
- Browser compatibility
- Mobile responsiveness

## Running Tests

### All Tests
```bash
make test
```

### Specific Test
```bash
bash tests/integration/test-ocp-login.sh
```

### Health Check
```bash
curl http://localhost/health.php
```

## Test Files

| File | Description |
|------|-------------|
| test-ocp-login.sh | Login flow |
| test-ocp-dark-mode.sh | Dark mode |
| test-ocp-new-pages.sh | New pages |
| test-ocp-profile-search.sh | Profile and search |
| test-ocp-system-health.sh | System health |
| test-ocp-mobile-responsive.sh | Mobile |
| test-ocp-bookmarks.sh | Bookmarks |
| test-ocp-reports.sh | Reports |
| test-ocp-scheduled-tasks.sh | Tasks |
| test-ocp-cache-manager.sh | Cache |
| test-ocp-system-logs.sh | Logs |

## Writing Tests

### Template
```bash
#!/usr/bin/env bash
set -euo pipefail

BASE="${TSISIP_BASE_URL:-http://localhost}"
COOKIE_JAR="/tmp/test_cookies_$$"

echo "=== Test: Feature ==="

# Login
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
  -X POST "$BASE/login.php" \
  -d "username=admin&password=admin123" \
  -L | grep -q "dashboard" && echo "[PASS] Login"

# Test
curl -s "$BASE/feature.php" | grep -q "Expected" && echo "[PASS] Feature"

rm -f "$COOKIE_JAR"
echo "=== Tests Passed ==="
```

## Best Practices

1. Use cookie jar for session
2. Clean up temp files
3. Check HTTP status
4. Verify page content
5. Test edge cases

## CI/CD

### GitHub Actions
```yaml
- name: Test
  run: make test
```

## Debugging

### Verbose
```bash
bash -x tests/integration/test-ocp-login.sh
```

### Logs
```bash
docker compose logs -f
```
