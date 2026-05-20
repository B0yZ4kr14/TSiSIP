# Evidence: B10 — Healthcheck OPENSIPS_HOST Explicit

## Finding
- **ID**: B10
- **Severity**: LOW
- **Category**: Config Rot
- **File**: `docker/healthcheck/opensips-health.sh:8`
- **Finding**: Default `127.0.0.1` for `OPENSIPS_HOST` could mask misconfiguration

## Fix Applied
Added explicit `OPENSIPS_HOST: 127.0.0.1` environment variable to all Docker Compose files where it was missing.

### Files Modified
| File | Change |
|---|---|
| `docker-compose.yml` | Added `OPENSIPS_HOST: 127.0.0.1` to opensips service env |
| `docker-compose.prod.yml` | Added `OPENSIPS_HOST: 127.0.0.1` to opensips service env |

### Note
`docker-compose.vps.yml` already had `OPENSIPS_HOST: 127.0.0.1` defined (line 96).

## Rationale
The healthcheck script uses `${OPENSIPS_HOST:-127.0.0.1}` with a fallback. While the fallback is correct for a single-container deployment (OpenSIPS listens on localhost inside its own container), making the variable explicit:
1. Documents the intended behavior for operators
2. Prevents silent misconfiguration if the default ever changes
3. Makes the healthcheck contract transparent

## Verification
All three compose files now explicitly set `OPENSIPS_HOST` for the opensips service.
