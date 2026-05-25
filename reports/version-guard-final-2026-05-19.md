# TSiSIP Version Guard Report

**Project:** `/home/b0yz4kr14/Projects/TSiSIP`  
**Run Date:** 2026-05-19  
**Mode:** `--strict --format=markdown`  
**Scope:** Focused verification of 6 pinned-version and security-binding constraints

---

## Check 1 — Certbot Dockerfile: FROM line includes both tag and digest

| Field | Value |
|---|---|
| **File** | `docker/certbot/Dockerfile` |
| **Line** | 1 |
| **Actual** | `FROM certbot/certbot:v5.6.0@sha256:0107d084c225631fc64a8313e19adb07275f7296fde338f7dfa93986c80b2e3e` |
| **Tag** | `v5.6.0` |
| **Digest** | `sha256:0107d084c225631fc64a8313e19adb07275f7296fde338f7dfa93986c80b2e3e` |
| **Status** | ✅ **PASS** |

---

## Check 2 — Tailscale-Cert Dockerfile: FROM line includes both tag and digest

| Field | Value |
|---|---|
| **File** | `docker/tailscale-cert/Dockerfile` |
| **Line** | 1 |
| **Actual** | `FROM tailscale/tailscale:v1.96.5@sha256:dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6` |
| **Tag** | `v1.96.5` |
| **Digest** | `sha256:dbeff02d2337344b351afac203427218c4d0a06c43fc10a865184063498472a6` |
| **Status** | ✅ **PASS** |

---

## Check 3 — docker-compose.yml alertmanager: image includes both tag and digest

| Field | Value |
|---|---|
| **File** | `docker-compose.yml` |
| **Line** | 434 |
| **Actual** | `image: prom/alertmanager:v0.27.0@sha256:e13b6ed5cb929eeaee733479dce55e10eb3bc2e9c4586c705a4e8da41e5eacf5` |
| **Tag** | `v0.27.0` |
| **Digest** | `sha256:e13b6ed5cb929eeaee733479dce55e10eb3bc2e9c4586c705a4e8da41e5eacf5` |
| **Status** | ✅ **PASS** |

> Note: `docker-compose.prod.yml` line 283 also carries the identical pinned image reference.

---

## Check 4 — OpenSIPS Dockerfile CMD: `-M 64` (not 32)

| Field | Value |
|---|---|
| **File** | `Dockerfile` (OpenSIPS root image) |
| **Line** | 51 |
| **Actual** | `CMD ["/usr/local/sbin/opensips", "-F", "-f", "/etc/opensips/opensips.cfg", "-m", "512", "-M", "64"]` |
| **Shared-Memory Flag** | `-M 64` |
| **Status** | ✅ **PASS** |

---

## Check 5 — docker-compose.prod.yml postgres: `shm_size` present

| Field | Value |
|---|---|
| **File** | `docker-compose.prod.yml` |
| **Line** | 7 |
| **Actual** | `shm_size: 2gb` |
| **Status** | ✅ **PASS** |

> Cross-reference: `docker-compose.yml` line 11 also sets `shm_size: 2gb` for the dev stack.

---

## Check 6 — No `0.0.0.0` in RTPengine `listen-ng`

| File | Line | Actual Binding | Status |
|---|---|---|---|
| `docker-compose.yml` | 152 | `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` | ✅ No `0.0.0.0` |
| `docker-compose.prod.yml` | 67 | `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` | ✅ No `0.0.0.0` |
| `docker-compose.vps.yml` | 106 | `--listen-ng=${RTPENGINE_INTERNAL_IP}:22222` | ✅ No `0.0.0.0` |

| **Status** | ✅ **PASS** |

---

## Gate Status

| Check | Result |
|---|---|
| 1. Certbot FROM tag + digest | ✅ PASS |
| 2. Tailscale FROM tag + digest | ✅ PASS |
| 3. Alertmanager image tag + digest | ✅ PASS |
| 4. OpenSIPS `-M 64` | ✅ PASS |
| 5. Postgres `shm_size` in prod compose | ✅ PASS |
| 6. RTPengine `listen-ng` ≠ `0.0.0.0` | ✅ PASS |

### **OVERALL GATE: 🟢 ALL PASSED**

All 6 focused version-guard constraints are satisfied. No blocking findings.
