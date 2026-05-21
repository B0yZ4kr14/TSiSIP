# Speckit Version Guard Report — 2026-05-21

> Dependency version pinning, Docker image consistency, and version drift detection.

## Summary

| Category | Status | Findings |
|---|---|---|
| Docker base image SHA pinning | PASS | All 15 Dockerfiles use `@sha256:` digests |
| Compose prod image pinning | PASS | All images use `:${TSISIP_IMAGE_TAG:?must be set}` |
| Compose dev image pinning | **FAIL** | 2 services use `:-latest` fallback |
| Compose VPS image pinning | **FAIL** | 1 service uses `:-latest` fallback |
| Python requirements pinning | **FAIL** | `>=` ranges instead of `==` pins |
| GitHub Actions versions | **WARN** | 1 action uses `@master` branch |
| Dockerfile ARG versions | PASS | OpenSIPS 3.6, Asterisk 20.9.3 explicitly versioned |

---

## Findings

### V1 — MEDIUM — Mutable Image Tags in docker-compose.yml (Development)

| Field | Value |
|---|---|
| **File** | `docker-compose.yml` |
| **Services** | `certbot`, `tailscale-cert` |
| **Finding** | `image: tsisip/certbot:${TSISIP_IMAGE_TAG:-latest}` and `image: tsisip/tailscale-cert:${TSISIP_IMAGE_TAG:-latest}` |
| **Impact** | Development environment may silently pull mutable `latest` tags if `TSISIP_IMAGE_TAG` is unset, causing "works on my machine" drift between dev and prod. |
| **Recommendation** | Change both to `:?must be set` to match `docker-compose.prod.yml` and `docker-compose.vps.yml` (except certbot in vps which is also V2). |

### V2 — MEDIUM — Mutable Image Tag in docker-compose.vps.yml (Already Reported as B2)

| Field | Value |
|---|---|
| **File** | `docker-compose.vps.yml` |
| **Service** | `certbot` |
| **Finding** | `image: tsisip/certbot:${TSISIP_IMAGE_TAG:-latest}` |
| **Recommendation** | Change to `:?must be set` (same fix as brownfield B2). |

### V3 — MEDIUM — Python Requirements Use Loose Version Ranges

| Field | Value |
|---|---|
| **File** | `docker/anomaly-detector/requirements.txt` |
| **Finding** | All dependencies use `>=` instead of `==`:
- `redis>=5.0.0`
- `prometheus-client>=0.20.0`
- `flask>=3.0.0`
- `numpy>=1.26.0`
- `requests>=2.31.0` |
| **Impact** | Builds are non-deterministic. A future upstream release could break the anomaly detector or introduce CVEs without code changes. |
| **Recommendation** | Pin to exact versions (`==`) and use a lock file (`requirements.lock.txt` or `pip freeze`) for reproducible builds. |

### V4 — LOW — Python Exporter Requirements Not Visible

| Field | Value |
|---|---|
| **File** | `docker/opensips-exporter/Dockerfile` |
| **Finding** | `RUN pip install --no-cache-dir` without a visible `requirements.txt`. |
| **Impact** | Cannot audit which packages are installed or verify pinning. |
| **Recommendation** | Add a `requirements.txt` with pinned versions and `COPY` it before `pip install`. |

### V5 — LOW — GitHub Action Uses Master Branch

| Field | Value |
|---|---|
| **File** | `.github/workflows/ci.yml` |
| **Action** | `uses: aquasecurity/trivy-action@master` |
| **Impact** | `@master` is a floating reference. A breaking change in Trivy Action could break CI without warning. |
| **Recommendation** | Pin to a specific tag or SHA, e.g., `aquasecurity/trivy-action@0.24.0`. |

---

## Passed Checks

| Check | Evidence |
|---|---|
| OpenSIPS version pinned | `Dockerfile: ARG OPENSIPS_VERSION=3.6` |
| Asterisk version pinned | `docker/asterisk/Dockerfile: ARG ASTERISK_VERSION=20.9.3` |
| All base images SHA-pinned | 15/15 Dockerfiles use `@sha256:` (verified by Architecture Guard) |
| Compose prod deterministic | All services use `:?must be set` |
| Prometheus client pinned | `docker/certbot-exporter/Dockerfile: prometheus_client==0.20.0` |
| GitHub Actions mostly pinned | `actions/checkout@v4`, `docker/setup-buildx-action@v3`, `webfactory/ssh-agent@v0.9.0` |

---

**Next check**: After dependency updates, Dockerfile changes, or CI workflow edits.
