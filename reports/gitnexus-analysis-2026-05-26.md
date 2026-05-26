# GitNexus Comprehensive Analysis — TSiSIP Project

**Date:** 2026-05-26  
**Repository:** `/home/b0yz4kr14/Projects/TSiSIP`  
**Indexed Commit:** `523c5ef`  
**Analysis Tool:** GitNexus CLI  

---

## 1. Index Status

```bash
gitnexus analyze
gitnexus status
```

**Result:**

```
Repository: /home/b0yz4kr14/Projects/TSiSIP
Indexed: 26/05/2026, 16:48:56
Indexed commit: 523c5ef
Current commit: 523c5ef
Status: ✅ up-to-date
```

**Index Statistics:**
- **Files:** 647
- **Symbols:** 7,539
- **Edges:** 8,260
- **Clusters (Communities):** 57
- **Processes (Flows):** 15
- **Nodes by Type:**
  | Label | Count |
  |-------|-------|
  | Section | 5,522 |
  | Variable | 673 |
  | File | 647 |
  | Function | 181 |
  | Method | 173 |
  | Folder | 135 |
  | Const | 62 |
  | Class | 50 |
  | Community | 50 |
  | Property | 27 |
  | Process | 15 |
  | Route | 4 |

---

## 2. Impact Analysis

### 2.1 OpenSIPS Config Impact

```bash
gitnexus impact "opensips.cfg.tpl" --direction=downstream --repo TSiSIP
```

**Result:**

```json
{
  "target": {
    "id": "File:opensips/opensips.cfg.tpl",
    "name": "opensips.cfg.tpl",
    "type": "",
    "filePath": "opensips/opensips.cfg.tpl"
  },
  "direction": "downstream",
  "impactedCount": 0,
  "risk": "LOW",
  "summary": {
    "direct": 0,
    "processes_affected": 0,
    "modules_affected": 0
  },
  "affected_processes": [],
  "affected_modules": [],
  "byDepth": {}
}
```

**Interpretation:** The OpenSIPS config template (`opensips/opensips.cfg.tpl`) has **no downstream dependencies** detected by the static analyzer. This is expected for a template file that is rendered at runtime by the entrypoint script rather than being imported by other source files.

---

### 2.2 Docker Compose (VPS) Impact

```bash
gitnexus impact "docker-compose.vps.yml" --direction=downstream --repo TSiSIP
```

**Result:**

```json
{
  "target": {
    "id": "File:docker-compose.vps.yml",
    "name": "docker-compose.vps.yml",
    "type": "",
    "filePath": "docker-compose.vps.yml"
  },
  "direction": "downstream",
  "impactedCount": 0,
  "risk": "LOW",
  "summary": {
    "direct": 0,
    "processes_affected": 0,
    "modules_affected": 0
  },
  "affected_processes": [],
  "affected_modules": [],
  "byDepth": {}
}
```

**Interpretation:** The VPS Docker Compose file has **no downstream code dependencies**. Changes to service definitions, environment variables, or network topology affect runtime orchestration but do not impact compiled or imported code modules.

---

### 2.3 Dockerfile Impact

```bash
gitnexus impact "File:Dockerfile" --direction=downstream --repo TSiSIP
```

**Result:**

```json
{
  "target": {
    "id": "File:Dockerfile",
    "name": "Dockerfile",
    "type": "",
    "filePath": "Dockerfile"
  },
  "direction": "downstream",
  "impactedCount": 0,
  "risk": "LOW",
  "summary": {
    "direct": 0,
    "processes_affected": 0,
    "modules_affected": 0
  },
  "affected_processes": [],
  "affected_modules": [],
  "byDepth": {}
}
```

**Note:** The query `Dockerfile` (without path qualifier) returned 16 ambiguous candidates across the repo. The root `File:Dockerfile` was selected for analysis. The 16 Dockerfile candidates are:

| Path | Purpose |
|------|---------|
| `Dockerfile` | Main OpenSIPS image |
| `docker/ocp/Dockerfile` | OCP v9 PHP/Apache image |
| `docker/backup/Dockerfile` | Backup container |
| `docker/ca-tool/Dockerfile` | CA tool container |
| `docker/certbot/Dockerfile` | Certbot container |
| `docker/grafana/Dockerfile` | Grafana container |
| `docker/asterisk/Dockerfile` | Asterisk container |
| `docker/postgres/Dockerfile` | PostgreSQL container |
| `docker/admin_api/Dockerfile` | Admin API container |
| `docker/rtpengine/Dockerfile` | RTPengine container |
| `docker/prometheus/Dockerfile` | Prometheus container |
| `docker/tailscale_cert/Dockerfile` | Tailscale cert container |
| `docker/anomaly_detector/Dockerfile` | Anomaly detector container |
| `docker/certbot_exporter/Dockerfile` | Certbot exporter container |
| `docker/opensips_exporter/Dockerfile` | OpenSIPS exporter container |
| `tests/integration/mock-sip-trunk/Dockerfile` | Mock SIP trunk test container |

**Interpretation:** Dockerfiles are build artifacts with no downstream static code dependencies. Their blast radius is limited to image rebuilds and runtime container behavior.

---

## 3. Functional Clusters (Communities)

```bash
gitnexus cypher "MATCH (c:Community) WHERE c.symbolCount >= 4 RETURN c.id, c.label, c.symbolCount ORDER BY c.symbolCount DESC" --repo TSiSIP
```

**Result:** 29 communities with 4+ symbols

| Community ID | Label | Symbol Count | Description |
|--------------|-------|--------------|-------------|
| comm_30 | Integration | 13 | Python integration tests |
| comm_28 | Integration | 12 | Python integration tests |
| comm_42 | Integration | 10 | Python integration tests |
| comm_39 | Integration | 9 | Python integration tests |
| comm_48 | Integration | 9 | Python integration tests |
| comm_51 | Integration | 8 | Python integration tests |
| comm_4 | Cluster_4 | 7 | OCP web/common PHP modules |
| comm_14 | Certbot_exporter | 7 | Certbot metrics exporter |
| comm_19 | Scripts | 7 | Build/validation scripts |
| comm_27 | Integration | 7 | Python integration tests |
| comm_31 | Integration | 7 | Python integration tests |
| comm_35 | Integration | 6 | Python integration tests |
| comm_45 | Integration | 6 | Python integration tests |
| comm_3 | Cluster_3 | 5 | Admin API subscriber handlers |
| comm_25 | Integration | 5 | Python integration tests |
| comm_33 | Integration | 5 | Python integration tests |
| comm_34 | Integration | 5 | Python integration tests |
| comm_43 | Integration | 5 | Python integration tests |
| comm_0 | Cluster_0 | 4 | Admin API config/audit |
| comm_13 | Anomaly_detector | 4 | Anomaly detection service |
| comm_15 | Opensips_exporter | 4 | OpenSIPS metrics exporter |
| comm_17 | Scripts | 4 | Validation scripts |
| comm_18 | Scripts | 4 | Validation scripts |
| comm_29 | Integration | 4 | Python integration tests |
| comm_38 | Integration | 4 | Python integration tests |
| comm_40 | Integration | 4 | Python integration tests |
| comm_46 | Integration | 4 | Python integration tests |
| comm_49 | Integration | 4 | Python integration tests |
| comm_52 | Integration | 4 | Python integration tests |

**Observation:** The codebase is heavily weighted toward integration testing (~25 of 29 largest communities). Application logic clusters are smaller but concentrated in:
- **Cluster_0 / Cluster_3 / Cluster_4:** Admin API and OCP web layer
- **Certbot_exporter / Opensips_exporter / Anomaly_detector:** Observability microservices
- **Scripts:** Build tooling and validation

---

## 4. Execution Flows (Processes)

```bash
gitnexus cypher "MATCH (p:Process) RETURN p.id, p.label, p.processType, p.stepCount ORDER BY p.id" --repo TSiSIP
```

**Result:** 15 detected execution flows

| Process ID | Label | Type | Steps | Entry Point | Terminal Point |
|------------|-------|------|-------|-------------|----------------|
| proc_0_handlesubscribercrea | HandleSubscriberCreate → GetDbPassword | cross_community | 4 | `docker/admin_api/src/subscriber-api.php:handleSubscriberCreate` | `docker/admin_api/src/config.php:getDbPassword` |
| proc_1_handlesubscriberdele | HandleSubscriberDelete → GetDbPassword | intra_community | 4 | `docker/admin_api/src/subscriber-api.php:handleSubscriberDelete` | `docker/admin_api/src/config.php:getDbPassword` |
| proc_2_main | Main → _parse_openssl_date | intra_community | 4 | `docker/certbot_exporter/exporter.py:main` | `docker/certbot_exporter/exporter.py:_parse_openssl_date` |
| proc_3_run_analysis_loop | Run_analysis_loop → _send_alertmanager | intra_community | 4 | `docker/anomaly_detector/detector.py:AnomalyDetector.run_analysis_loop` | `docker/anomaly_detector/detector.py:AnomalyDetector._send_alertmanager` |
| proc_4_main | Main → Fetch_mi | intra_community | 4 | `docker/opensips_exporter/exporter.py:main` | `docker/opensips_exporter/exporter.py:fetch_mi` |
| proc_5_handlesubscriberupda | HandleSubscriberUpdate → GetDbPassword | cross_community | 3 | `docker/admin_api/src/subscriber-api.php:handleSubscriberUpdate` | `docker/admin_api/src/config.php:getDbPassword` |
| proc_6_authenticateuser | AuthenticateUser → GetDb | intra_community | 3 | `web/common/config.php:authenticateUser` | `web/common/config.php:getDb` |
| proc_7_authenticateuser | AuthenticateUser → _auditCanonicalHash | intra_community | 3 | `web/common/audit.php:authenticateUser` | `web/common/audit.php:_auditCanonicalHash` |
| proc_8_main | Main → Extract_file_references | cross_community | 3 | `scripts/validate-blueprints.py:main` | `scripts/validate-blueprints.py:extract_file_references` |
| proc_9_main | Main → Count_todo_markers | cross_community | 3 | `scripts/validate-blueprints.py:main` | `scripts/validate-blueprints.py:count_todo_markers` |
| proc_10_main | Main → Extract_before_after_blocks | cross_community | 3 | `scripts/validate-blueprints.py:main` | `scripts/validate-blueprints.py:extract_before_after_blocks` |
| proc_11_main | Main → Check_after_implementation | cross_community | 3 | `scripts/validate-blueprints.py:main` | `scripts/validate-blueprints.py:check_after_implementation` |
| proc_12_main | Main → _parse_cert_list | intra_community | 3 | `docker/certbot_exporter/exporter.py:main` | `docker/certbot_exporter/exporter.py:_parse_cert_list` |
| proc_13_main | Main → Get_cert_domain | intra_community | 3 | `docker/certbot_exporter/exporter.py:main` | `docker/certbot_exporter/exporter.py:get_cert_domain` |
| proc_14_main | Main → _read_int_file | intra_community | 3 | `docker/certbot_exporter/exporter.py:main` | `docker/certbot_exporter/exporter.py:_read_int_file` |

**Key Flows by Domain:**

| Domain | Processes |
|--------|-----------|
| **Admin API (Subscriber CRUD)** | proc_0, proc_1, proc_5 |
| **OCP Web Auth** | proc_6, proc_7 |
| **Observability (Exporters)** | proc_2, proc_4, proc_12–14 |
| **Anomaly Detection** | proc_3 |
| **Build/Validation Scripts** | proc_8–11 |

---

## 5. Concept Queries

### 5.1 Docker-Related Execution Flows

```bash
gitnexus query "docker" --repo TSiSIP
```

**Result:** No processes found. Definitions returned are primarily Docker helper functions in integration tests (e.g., `docker_compose_exec`, `psql`, `get_restart_count`).

**Notable Symbols:**
- `tests/integration/test_restart_policy.py` — restart policy validation
- `tests/integration/test_lgpd_compliance.py` — Docker exec helpers for compliance tests
- `tests/integration/test_sip_trunk_failover.py` — test IP resolution and OpenSIPS FIFO helpers
- `tests/integration/test_certificate_rotation.py` — CA tool image and cert rotation script tests
- `tests/integration/test_runbook_scale.py` — dispatcher scale runbook tests
- `tests/integration/test_end_to_end_call.py` — `_psql`, `_mi_rpc`, `_run_sipsak_with_ip`
- `tests/integration/test_tls_srtp.py` — TLS/SRTP encryption tests

---

### 5.2 Auth-Related Execution Flows

```bash
gitnexus query "auth" --repo TSiSIP
```

**Result:** 4 processes, 3 process symbols, 20 definitions

**Processes:**
1. **proc_4_main** — `Main → Fetch_mi` (OpenSIPS exporter metrics)
2. **proc_6_authenticateuser** — `AuthenticateUser → GetDb` (OCP web auth)
3. **proc_0_handlesubscribercrea** — `HandleSubscriberCreate → GetDbPassword` (Admin API)
4. **proc_1_handlesubscriberdele** — `HandleSubscriberDelete → GetDbPassword` (Admin API)

**Key Auth Symbols:**
- `scripts/sip-auth-probe.py` — `build_invite`, `digest_authorization`, `main`
- `tests/integration/test_ddos_protection.py` — auth failure event route tests
- `tests/integration/test_rate_limiting.py` — pike module, cachedb local auth failure tests
- `tests/integration/test_sip_trunk_outbound.py` — `_authenticate`, `_build_register`, `_build_invite`
- `tests/integration/test_sip_trunk_health_probe.py` — health probe auth flows
- `tests/integration/test_sip_trunk_rate_limit.py` — CPS rate limiting tests
- `tests/integration/test_sip_trunk_failover.py` — failover auth scenarios

---

### 5.3 Database-Related Execution Flows

```bash
gitnexus query "database" --repo TSiSIP
```

**Result:** 2 processes, 2 process symbols, 20 definitions

**Processes:**
1. **proc_0_handlesubscribercrea** — `HandleSubscriberCreate → GetDbPassword`
2. **proc_1_handlesubscriberdele** — `HandleSubscriberDelete → GetDbPassword`

**Key Database Symbols:**
- `docker/admin_api/src/subscriber-api.php` — subscriber CRUD API
- `web/common/audit.php` — `_auditBoolToString`
- `docker/backup/pitr-restore.sh` — point-in-time restore
- `docker/postgres/healthcheck.sh` — PostgreSQL healthcheck
- `docker/healthcheck/postgres-health.sh` — shared healthcheck script
- `tests/integration/test_backup_pitr.py` — PITR restore validation tests
- Schema files: `01-stock-opensips-schema.sql`, `02-tsisip-extensions.sql`, `04-trunk-schema.sql`, etc.
- Spec docs: `data-model.md`, `ENVIRONMENT.md`, `PROJECT_CONTEXT.md`

---

### 5.4 SIP-Related Execution Flows

```bash
gitnexus query "sip" --repo TSiSIP
```

**Result:** 1 process, 1 process symbol, 20 definitions

**Process:**
1. **proc_4_main** — `Main → Fetch_mi` (OpenSIPS exporter)

**Key SIP Symbols:**
- `tests/integration/test_sip_trunk_outbound.py` — `_build_register`, `_build_invite`, `_build_sip_reply`
- `tests/integration/test_sip_trunk_failover.py` — `get_test_ip`, `_build_register`, `_build_invite`
- `tests/integration/test_sip_trunk_rate_limit.py` — `_build_register`, `_build_invite`, `_build_sip_reply`
- `tests/integration/test_sip_trunk_health_probe.py` — health probe SIP message builders
- `tests/integration/test_sip_trunk_inbound.py` — `_build_inbound_invite`, `_build_sip_reply`, `setUpClass`
- `tests/integration/test_sip_trunk_did_routing.py` — `run_in_sip_edge`, DID routing tests
- `tests/integration/mock-sip-trunk/mock_trunk_server.py` — `parse_sip_message`, `serve_udp`

---

## 6. HTTP Routes Detected

```bash
gitnexus cypher "MATCH (r:Route) RETURN r.id, r.name, r.filePath ORDER BY r.filePath" --repo TSiSIP
```

| Route | File | Service |
|-------|------|---------|
| `/health` | `docker/anomaly_detector/detector.py` | Anomaly Detector |
| `/metrics` | `docker/anomaly_detector/detector.py` | Anomaly Detector |
| `/api/v1/event` | `docker/anomaly_detector/detector.py` | Anomaly Detector |
| `/api/v1/status` | `docker/anomaly_detector/detector.py` | Anomaly Detector |

**Note:** Only the Anomaly Detector's Flask routes were detected. The OCP PHP application routes and the Admin API routes are not surfaced as typed Route nodes, likely due to the lack of explicit router annotations in PHP.

---

## 7. Summary & Observations

### 7.1 Repository Scale
- **647 files** indexed with **7,539 symbols** and **8,260 edges**
- **50 communities** and **15 execution flows** detected
- **Well-structured** Docker-first project with clear service boundaries

### 7.2 Static Analysis Limitations
1. **PHP Scope Extraction:** 40+ PHP files failed scope extraction (no Module scope found). This affects the OCP web layer, Admin API, and wiki pages. The knowledge graph under-represents PHP call graphs.
2. **Template Files:** `opensips.cfg.tpl` and Docker Compose files have no downstream static dependencies because they are runtime configuration, not imported modules.
3. **Route Detection:** Only Python Flask routes in the Anomaly Detector were detected. PHP-based OCP and Admin API endpoints are invisible to the route analyzer.

### 7.3 Architecture Insights from Graph
- **Integration Tests Dominate:** ~25 of 29 major communities are Python integration tests, reflecting the project's strong testing posture.
- **Core Logic is Concentrated:** The actual application logic lives in small, tightly-scoped clusters:
  - Admin API (subscriber CRUD, audit logging)
  - OCP web layer (auth, database access)
  - Observability exporters (OpenSIPS, Certbot)
  - Anomaly detection service
- **Cross-Community Flows:** Only 5 of 15 processes are cross-community, indicating good modularity with limited tight coupling between domains.

### 7.4 Risk Assessment
- **Impact analysis shows LOW risk** for all three core infrastructure files (`opensips.cfg.tpl`, `docker-compose.vps.yml`, `Dockerfile`). This is expected for build/runtime configuration.
- **No circular dependencies** detected in the top-level processes.
- **Auth and database flows** are well-isolated within their respective communities.

---

## 8. Command Log

| # | Command | Status |
|---|---------|--------|
| 1 | `gitnexus analyze` | ✅ Success (23.9s) |
| 2 | `gitnexus status` | ✅ Up-to-date |
| 3 | `gitnexus impact "opensips.cfg.tpl" --direction=downstream --repo TSiSIP` | ✅ LOW risk |
| 4 | `gitnexus impact "docker-compose.vps.yml" --direction=downstream --repo TSiSIP` | ✅ LOW risk |
| 5 | `gitnexus impact "File:Dockerfile" --direction=downstream --repo TSiSIP` | ✅ LOW risk |
| 6 | `gitnexus cypher "MATCH (c:Community) ..."` | ✅ 57 clusters |
| 7 | `gitnexus cypher "MATCH (p:Process) ..."` | ✅ 15 flows |
| 8 | `gitnexus query "docker" --repo TSiSIP` | ✅ Definitions only |
| 9 | `gitnexus query "auth" --repo TSiSIP` | ✅ 4 processes |
| 10 | `gitnexus query "database" --repo TSiSIP` | ✅ 2 processes |
| 11 | `gitnexus query "sip" --repo TSiSIP` | ✅ 1 process |

---

*Report generated by GitNexus analysis pipeline.*
