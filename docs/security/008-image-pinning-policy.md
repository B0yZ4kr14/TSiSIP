# Image Pinning Policy — Feature 008: DevSecOps Deployment Automation

**Document ID**: SEC-008-PIN-001  
**Date**: 2026-05-19  
**Applies to**: All container images in compose files  
**Review cycle**: Per release  

---

## 1. Policy

Every container image reference MUST use one of:

1. **SHA256 digest** (strongest): `image@sha256:abc123...`
2. **Semantic version tag** (strong): `image:v1.2.3`
3. **Git SHA tag** (strong): `image:git-abc1234`

`:latest` is **FORBIDDEN** in production unless explicitly justified and documented.

---

## 2. Current State

### Project-Owned Images (tsisip/*)

All project-owned images use `${TSISIP_IMAGE_TAG:?must be set}` which is injected at build time. The CI pipeline sets this to the git SHA or semantic version.

| Service | Image Reference | Status |
|---|---|---|
| opensips | `tsisip/opensips:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| postgres | `tsisip/postgres:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| rtpengine | `tsisip/rtpengine:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| asterisk | `tsisip/asterisk:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| ocp | `tsisip/ocp:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| prometheus | `tsisip/prometheus:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| anomaly-detector | `tsisip/anomaly-detector:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| grafana | `tsisip/grafana:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| opensips-exporter | `tsisip/opensips-exporter:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| certbot-exporter | `tsisip/certbot-exporter:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |
| backup | `tsisip/backup:${TSISIP_IMAGE_TAG}` | OK (build-time variable) |

### Third-Party Images

| Service | Image | Tag Type | Status |
|---|---|---|---|
| alertmanager | `prom/alertmanager:v0.27.0` | Semantic version | OK |
| certbot | `tsisip/certbot:${TSISIP_IMAGE_TAG:-latest}` | Build-time variable with `:latest` fallback | **ACCEPTED RISK** |
| tailscale-cert | `tsisip/tailscale-cert:${TSISIP_IMAGE_TAG:-latest}` | Build-time variable with `:latest` fallback | **ACCEPTED RISK** |

### Justification for `:latest` Fallback

The `certbot` and `tailscale-cert` services use `${TSISIP_IMAGE_TAG:-latest}` because:

1. They are **ephemeral CI builds** without persistent tags during initial development.
2. The fallback only activates when `TSISIP_IMAGE_TAG` is unset, which only happens in local developer environments.
3. Production deployments always set `TSISIP_IMAGE_TAG` to a git SHA or semantic version.
4. These images are not part of the critical SIP signaling path.

### Action Items

- [ ] Pin certbot image to semantic version once stable
- [ ] Pin tailscale-cert image to semantic version once stable

---

## 3. Enforcement

- `scripts/ci-scan.sh` checks for hardcoded `:latest` tags and fails the build.
- `deploy/validate.sh` verifies image references in compose files.

---

## 4. Sign-off

| Role | Name | Date | Status |
|---|---|---|---|
| Author | Security Governance | 2026-05-19 | Approved |
