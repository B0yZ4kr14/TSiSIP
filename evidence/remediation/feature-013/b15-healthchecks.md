# B15 — Missing Healthchecks Remediation

## Finding
The `backup` and `anomaly-detector` services lacked `healthcheck` blocks in Docker Compose files, meaning Docker had no runtime signal for service health.

## Changes Made

### 1. Created backup healthcheck script
**File:** `docker/backup/healthcheck.sh`

This script checks:
- If `/tmp/backup.lock` exists (backup is currently running) → healthy
- OR if there's at least one `.enc` file in `/backup/daily` newer than 1440 minutes (24h) → healthy
- Otherwise → unhealthy

The script is automatically copied into the backup image via the existing `COPY *.sh /usr/local/bin/` directive in `docker/backup/Dockerfile`.

### 2. Added healthcheck blocks to compose files

All healthchecks use:
- `interval: 15s`
- `timeout: 5s`
- `retries: 3`
- `start_period: 10s`

#### docker-compose.yml
- **anomaly-detector** (after `depends_on` block):
  ```yaml
  healthcheck:
    test: ["CMD-SHELL", "curl -fsSL http://localhost:8080/health || exit 1"]
    interval: 15s
    timeout: 5s
    retries: 3
    start_period: 10s
  ```

- **backup** (after `expose` block):
  ```yaml
  healthcheck:
    test: ["CMD-SHELL", "/usr/local/bin/healthcheck.sh"]
    interval: 15s
    timeout: 5s
    retries: 3
    start_period: 10s
  ```

#### docker-compose.prod.yml
- **anomaly-detector** (after `deploy` block):
  ```yaml
  healthcheck:
    test:
    - CMD-SHELL
    - curl -fsSL http://localhost:8080/health || exit 1
    interval: 15s
    timeout: 5s
    retries: 3
    start_period: 10s
  ```

- **backup** (after `deploy` block):
  ```yaml
  healthcheck:
    test:
    - CMD-SHELL
    - /usr/local/bin/healthcheck.sh
    interval: 15s
    timeout: 5s
    retries: 3
    start_period: 10s
  ```

#### docker-compose.vps.yml
- **backup** (after `depends_on` block):
  ```yaml
  healthcheck:
    test: ["CMD-SHELL", "/usr/local/bin/healthcheck.sh"]
    interval: 15s
    timeout: 5s
    retries: 3
    start_period: 10s
  ```

Note: `docker-compose.vps.yml` does **not** contain the `anomaly-detector` service by design (monitoring stack is disabled in the VPS profile), so no healthcheck was added for it there.

## Style Compliance
- `docker-compose.yml`: inline array style (matches existing postgres/opensips healthchecks)
- `docker-compose.prod.yml`: block list style (matches existing grafana/opensips-exporter healthchecks)
- `docker-compose.vps.yml`: inline array style (matches existing postgres healthcheck)

## Validation
```bash
# docker-compose.yml
docker compose config > /dev/null && echo "OK"

# docker-compose.prod.yml
TSISIP_IMAGE_TAG=latest docker compose -f docker-compose.prod.yml config > /dev/null && echo "OK"

# docker-compose.vps.yml
TSISIP_IMAGE_TAG=latest docker compose -f docker-compose.vps.yml config > /dev/null && echo "OK"
```

All three files passed `docker compose config` validation.
