# Evidence: B13 — Image Tag Pinning in Production

## Finding
- **ID**: B13
- **Severity**: LOW
- **Category**: Config Rot
- **File**: `docker-compose.vps.yml`, `docker-compose.prod.yml`
- **Finding**: Image tags use `latest` fallback which is unpinned

## Fix Applied
Removed `latest` fallback in production compose files.

### Changes
| File | Before | After |
|---|---|---|
| `docker-compose.prod.yml` | `${TSISIP_IMAGE_TAG:-latest}` | `${TSISIP_IMAGE_TAG:?must be set}` |
| `docker-compose.vps.yml` | `${TSISIP_IMAGE_TAG:-latest}` | `${TSISIP_IMAGE_TAG:?must be set}` |
| `.env.example` | `TSISIP_IMAGE_TAG=latest` | `TSISIP_IMAGE_TAG=v0.6.0-stabilized` |

### Rationale
`latest` is a moving target. In production, an unplanned image pull can introduce untested changes, break compatibility, and make rollbacks unpredictable. Using shell parameter expansion `:?` forces the operator to explicitly choose a tag.

### Unchanged
- `docker-compose.yml` (development) retains `latest` fallback — acceptable for dev
- `alertmanager` in prod already pinned to specific version

## Verification
All production services now require explicit `TSISIP_IMAGE_TAG` at runtime.
