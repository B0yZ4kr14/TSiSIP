# Feature 013: Brownfield Residual Findings Remediation — QA Validation Report

**Date:** 2026-05-19
**QA Agent:** w6-qa-validation
**Project:** TSiSIP

---

## Summary

| # | Validation Task | Result |
|---|----------------|--------|
| 1 | Run `scripts/ci-scan.sh` | **PASS** |
| 2 | Run `docker compose config` on all three compose files | **PASS*** |
| 3 | Verify `docker/backup/backup.sh` syntax (`bash -n`) | **PASS** |
| 4 | Verify `docker/backup/healthcheck.sh` syntax (`bash -n`) | **PASS** |
| 5 | Check all services in all compose files have `restart` policies | **PASS** |
| 6 | Check backup and anomaly-detector services have `healthcheck` blocks | **FAIL** |
| 7 | Confirm no `ALLOW_UNENCRYPTED_BACKUPS` references in `backup.sh` | **PASS** |

> *Compose files require the `TSISIP_IMAGE_TAG` environment variable to be set. With the variable provided, all three files parse successfully. Without it, `docker-compose.prod.yml` and `docker-compose.vps.yml` fail with interpolation errors.

---

## 1. CI Scan (`scripts/ci-scan.sh`)

**Result: PASS**

```
=== TSiSIP CI Scan ===
[brownfield] Checking for hardcoded :latest tags...
PASS: No hardcoded :latest tags
[brownfield] Checking for forbidden modules...
PASS: No forbidden modules
[version-guard] Checking for unpinned base images...
PASS: Base image check complete
[memorylint] Checking for container memory limits...
PASS: Memory limits present on 24 services
[security] Checking for committed secrets...
PASS: No tracked secret files

=== CI SCAN PASSED ===
```

---

## 2. Docker Compose Config Validation

### 2a. `docker-compose.yml`

**Command:**
```bash
docker compose -f docker-compose.yml config
```

**Result: PASS**

```
docker-compose.yml: PASS
```

---

### 2b. `docker-compose.prod.yml`

**Command (bare):**
```bash
docker compose -f docker-compose.prod.yml config
```

**Raw output (failure):**
```
error while interpolating services.anomaly-detector.image: required variable TSISIP_IMAGE_TAG is missing a value: must be set
```

**Command (with required env var):**
```bash
TSISIP_IMAGE_TAG=latest docker compose -f docker-compose.prod.yml config
```

**Result: PASS**

```
docker-compose.prod.yml (with TSISIP_IMAGE_TAG): PASS
```

---

### 2c. `docker-compose.vps.yml`

**Command (bare):**
```bash
docker compose -f docker-compose.vps.yml config
```

**Raw output (failure):**
```
error while interpolating services.asterisk-pbx-2.image: required variable TSISIP_IMAGE_TAG is missing a value: must be set
```

**Command (with required env var):**
```bash
TSISIP_IMAGE_TAG=latest docker compose -f docker-compose.vps.yml config
```

**Result: PASS**

```
docker-compose.vps.yml (with TSISIP_IMAGE_TAG): PASS
```

---

## 3. `docker/backup/backup.sh` Syntax Check

**Command:**
```bash
bash -n docker/backup/backup.sh
```

**Result: PASS**

```
backup.sh syntax: PASS
```

---

## 4. `docker/backup/healthcheck.sh` Syntax Check

**Command:**
```bash
bash -n docker/backup/healthcheck.sh
```

**Result: PASS**

```
healthcheck.sh syntax: PASS
```

---

## 5. Restart Policy Audit

**Result: PASS** — All services in all compose files declare a `restart` policy.

### `docker-compose.yml`
| Service | `restart` |
|---------|-----------|
| postgres | `unless-stopped` |
| rtpengine | `on-failure` |
| opensips | `unless-stopped` |
| asterisk-pbx-1 | `on-failure` |
| asterisk-pbx-2 | `on-failure` |
| ocp | `on-failure` |
| prometheus | `on-failure` |
| alertmanager | `on-failure` |
| anomaly-detector | `on-failure` |
| grafana | `on-failure` |
| opensips-exporter | `on-failure` |
| backup | `on-failure` |

### `docker-compose.prod.yml`
| Service | `restart` |
|---------|-----------|
| postgres | `unless-stopped` |
| rtpengine | `on-failure` |
| opensips | `unless-stopped` |
| ocp | `on-failure` |
| prometheus | `on-failure` |
| alertmanager | `on-failure` |
| anomaly-detector | `on-failure` |
| grafana | `on-failure` |
| opensips-exporter | `on-failure` |
| backup | `on-failure` |
| asterisk-pbx-1 | `on-failure` |
| asterisk-pbx-2 | `on-failure` |

### `docker-compose.vps.yml`
| Service | `restart` |
|---------|-----------|
| postgres | `unless-stopped` |
| rtpengine | `on-failure` |
| opensips | `unless-stopped` |
| asterisk-pbx-1 | `unless-stopped` |
| asterisk-pbx-2 | `unless-stopped` |
| ocp | `unless-stopped` |
| backup | `on-failure` |

---

## 6. Healthcheck Block Audit

**Result: FAIL**

### `docker-compose.yml`
| Service | `healthcheck` |
|---------|---------------|
| backup | **PRESENT** |
| anomaly-detector | **PRESENT** |

### `docker-compose.prod.yml`
| Service | `healthcheck` |
|---------|---------------|
| backup | **PRESENT** |
| anomaly-detector | **PRESENT** |

### `docker-compose.vps.yml`
| Service | `healthcheck` |
|---------|---------------|
| backup | **PRESENT** |
| anomaly-detector | **MISSING** — Service does not exist in this file |

**Finding:** `docker-compose.vps.yml` does not define an `anomaly-detector` service at all. Consequently, it cannot have a `healthcheck` block. The services present in `docker-compose.vps.yml` are:

- postgres
- rtpengine
- opensips
- asterisk-pbx-1
- asterisk-pbx-2
- ocp
- backup

> **Recommendation:** Add the `anomaly-detector` service (with a `healthcheck` block) to `docker-compose.vps.yml` if it is intended to run in the VPS deployment profile.

---

## 7. `ALLOW_UNENCRYPTED_BACKUPS` Removal Verification

**Command:**
```bash
grep -n "ALLOW_UNENCRYPTED_BACKUPS" docker/backup/backup.sh
```

**Result: PASS**

```
EXIT_CODE=1
```

Exit code `1` confirms the string is **not present** anywhere in `docker/backup/backup.sh`.

---

## Final Verdict

| Overall | Status |
|---------|--------|
| CI Scan | PASS |
| Compose Syntax | PASS (with `TSISIP_IMAGE_TAG` env var) |
| Shell Syntax | PASS |
| Restart Policies | PASS |
| Healthcheck Blocks | **FAIL** — `anomaly-detector` missing from `docker-compose.vps.yml` |
| Unencrypted Backup Flag Removal | PASS |

**Blocking Issue:** Task 6 fails because `docker-compose.vps.yml` omits the `anomaly-detector` service entirely, making a `healthcheck` block impossible for that service in that compose file.

## QA Resolution Note

**Task 6 "FAIL" is a FALSE POSITIVE.**

`docker-compose.vps.yml` intentionally excludes the `anomaly-detector` service per the VPS-lite profile design (~4GB RAM, monitoring disabled). The file header explicitly states:

> "Monitoring completo (prometheus/grafana/alertmanager/exporter/anomaly-detector) fica desabilitado."

Therefore, no healthcheck is required (or possible) for `anomaly-detector` in `docker-compose.vps.yml`. The B15 remediation is complete for all services that actually exist in each compose profile.

**Corrected Task 6 Verdict: PASS (with documented exception)**
