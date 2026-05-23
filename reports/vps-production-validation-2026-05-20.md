# TSiSIP VPS Production Validation Report

**Date:** 2026-05-20
**Git Commit:** main branch (post Feature 009 deploy pipeline fixes)
**Report Status:** Partial — Deploy pipeline stabilized, VPS recovery in progress

---

## Executive Summary

The TSiSIP deploy pipeline (Feature 009) has undergone significant stabilization. Multiple root causes of deploy failures have been identified and resolved:

1. **RTPengine ENTRYPOINT/CMD separation** — Fixed init error
2. **Docker Compose build overlay** (`docker-compose.build.yml`) — Enables build-on-target fallback
3. **Postgres capabilities** — Added required caps for `cap_drop: ALL` environment
4. **Git branch sync** — Changed from `master` to `main`
5. **SSH key handling** — Fixed hardcoded path, now uses ssh-agent properly

The VPS experienced critical load (~166) during repeated deploy attempts. Recovery procedures have been documented. Core services (postgres, asterisk-pbx-1/2) remain healthy. rtpengine, opensips, ocp, and grafana are pending restart once load stabilizes.

---

## Resolved Issues

### 1. RTPengine Init Error (RESOLVED)

**Problem**: Container failed with `exec: "--interface=...": no such file or directory`

**Fix Applied**: Separated ENTRYPOINT and CMD in Dockerfile so compose `command:` array replaces CMD correctly.

### 2. GHCR Push Permission Denied (WORKAROUNDED)

**Problem**: `denied: permission_denied: write_package` despite `packages: write` in workflow permissions.

**Workaround**: Build-on-target fallback using `docker-compose.build.yml` overlay.

**Long-term Fix**: Investigate using a Personal Access Token with package write scope or configuring GHCR permissions for the repository token.

### 3. Docker Compose Build Context Mismatch (RESOLVED)

**Problem**: COPY commands failed during on-target build because build contexts were incorrect.

**Fix Applied**: Created `docker-compose.build.yml` with per-service contexts matching CI build job expectations.

### 4. Postgres Permission Denied (RESOLVED)

**Problem**: Postgres container failed under `cap_drop: ALL` because init scripts need file ownership changes.

**Fix Applied**: Added `cap_add: [CHOWN, SETUID, SETGID, DAC_OVERRIDE]` to postgres service.

### 5. Duplicate `--foreground` Flag (RESOLVED)

**Problem**: RTPengine command had duplicate `--foreground` (from Dockerfile CMD + compose command).

**Fix Applied**: Removed duplicate `--foreground` from `docker-compose.prod.yml` command array.

---

## Current VPS Status (As of 2026-05-20)

### Hardware
- **RAM**: 32GB total, ~26GB used during peak load
- **Disk**: 116GB total
- **Load**: Peaked at ~166, currently recovering

### Container Status

| Service | Status | Notes |
|---------|--------|-------|
| postgres | **Healthy** | Database operational |
| asterisk-pbx-1 | **Healthy** | PBX backend operational |
| asterisk-pbx-2 | **Healthy** | PBX backend operational |
| backup | **Unhealthy** | Under investigation |
| rtpengine | **Missing** | Pending restart after load recovery |
| opensips | **Missing** | Pending restart after load recovery |
| ocp | **Missing** | Pending restart after load recovery |
| grafana | **Missing** | Not in vps-lite profile; optional |
| certbot-exporter | **Restarting (1)** | Config issue; non-critical |
| tailscale-cert | **Restarting (1)** | Config issue; non-critical |

### Network Services
- **Nginx**: Operational (reverse proxy for OCP)
- **SSH**: Operational (intermittent timeouts under extreme load)

---

## Deploy Pipeline Changes

### New Files
- `docker-compose.build.yml` — Build context overlay for fallback mode

### Modified Files
- `docker/rtpengine/Dockerfile` — Fixed ENTRYPOINT/CMD separation
- `docker-compose.prod.yml` — Added postgres capabilities, removed duplicate flag
- `deploy/scripts/orchestrate-deploy.sh` — Fixed git branch (`main`), SSH key handling, build fallback
- `.github/workflows/deploy.yml` — Added artifact-based image transfer, packages write permission

---

## Recovery Playbook

### If VPS Load Spikes During Deploy

```bash
# 1. Kill stuck processes
sudo pkill -f "docker compose up"
sudo pkill -f "docker build"

# 2. Monitor load until < 10
watch -n 5 uptime

# 3. Restart core services in order
sudo docker compose -f docker-compose.prod.yml up -d postgres
sleep 15
sudo docker compose -f docker-compose.prod.yml up -d rtpengine
sleep 5
sudo docker compose -f docker-compose.prod.yml up -d opensips
sudo docker compose -f docker-compose.prod.yml up -d ocp
sudo docker compose -f docker-compose.prod.yml up -d

# 4. Verify
sudo docker compose -f docker-compose.prod.yml ps
sudo docker compose -f docker-compose.prod.yml logs opensips | tail -20
```

---

## Next Steps

1. **Complete VPS recovery** — Start missing containers once load < 10
2. **Fix GHCR push** — Configure proper PAT or token permissions
3. **Fix backup health** — Investigate backup container unhealthy status
4. **Fix certbot/tailscale loops** — Verify configuration and dependencies
5. **SSL Certificate** — Complete Cloudflare origin cert validation (HTTP 526)
6. **Run full verification** — SIP OPTIONS probe, OCP login, backup metrics

---

*Report generated by Kimi Code CLI*
