# Blueprint: Feature 012 — OCP Administrative Tools Restoration

## Overview

Restores critical administrative tools to the TSiSIP OCP frontend:
- **Subscriber Management** (`subscribers.php`) — CRUD with HA1 generation
- **CDR Viewer** (`cdr-viewer.php`) — Read-only filtered query
- **Dispatcher Management** (`dispatcher.php`) — CRUD with state toggle

## Files

| File | Purpose |
|---|---|
| `web/subscribers.php` | Subscriber CRUD (list, create, edit, toggle) |
| `web/cdr-viewer.php` | CDR read-only viewer (date range, tenant, status, from_user filters) |
| `web/dispatcher.php` | Dispatcher CRUD (list, create, edit, delete, toggle state) |
| `web/common/csrf.php` | CSRF token generation/validation |
| `web/common/pagination.php` | Reusable pagination helper |
| `web/common/ha1-generator.php` | HA1/HA1-SHA256/HA1-SHA512-256 generator |
| `web/common/role-nav.php` | Role-based navigation sidebar |
| `web/common/config.php` | Auth guards (`requireAuth`, `checkPasswordChange`, `requireRole`) |

## Security Architecture

- All DB queries use PDO prepared statements
- HA1 hashes only (no plaintext passwords)
- CSRF tokens on all mutating forms
- `requireRole('devops')` on all admin pages
- `checkPasswordChange()` + `requireAuth()` guards

## Testing

```bash
# Build OCP image
docker build -t tsisip/ocp:latest -f docker/ocp/Dockerfile .

# Validate compose
docker compose config
```

## Deployment

Via Feature 009 pipeline (`deploy/scripts/orchestrate-deploy.sh`) or GitHub Actions `workflow_dispatch`.
