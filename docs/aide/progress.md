# TSiSIP — Progress Tracking

> Generated from docs/aide/vision.md and docs/aide/roadmap.md
> Last updated: 2026-05-24

---

## Completed Capabilities

| Capability | Status | Feature |
|---|---|---|
| OpenSIPS 3.6 LTS Docker image | ✅ Complete | 001 |
| PostgreSQL schema + auth | ✅ Complete | 001 |
| Docker Compose 3-network topology | ✅ Complete | 001 |
| RTPengine media relay | ✅ Complete | 001 |
| Prometheus/Grafana observability | ✅ Complete | 003 |
| Health checks + autohealing | ✅ Complete | 004 |
| PostgreSQL backup/restore | ✅ Complete | 005 |
| Rate limiting + DDoS protection | ✅ Complete | 006 |
| TLS/SRTP encryption | ✅ Complete | 007 |
| DevSecOps deployment pipeline | ✅ Complete | 008 |
| OCP rebranding (TSiSIP theme) | ✅ Complete | 002 |
| OCP audit log compliance | ✅ Complete | 016 |
| SIP trunk provider integration | ✅ Complete | 017 |
| OCP critical tool gap closure | ✅ Complete | 020 |
| VPS go-live stabilization | ✅ Complete | 022 |
| Subscriber proxy API (ARCH-PRE-001) | ✅ Complete | 023 |
| Global FR-NNN-XXX ID migration | ✅ Complete | 018 |
| Feature 024 | ✅ Complete | 024 |

---

## Stage 1 — Brownfield Gap Closure & Schema Hardening

**Goal:** Resolve all outstanding medium/low findings from the 2026-05-19 brownfield scan and enforce schema consistency.

**Overall Status:** 🚧 In Progress

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 1.1 | Add RTPENGINE_INTERNAL_IP to .env.example with documented default and override behavior | 🚧 In Progress |
| 1.2 | Migrate sip_trunk_did_mappings.tenant_id from UUID to VARCHAR(36) to align with tenant ID schema | 🚧 In Progress |
| 1.3 | Update all DDL references, seed data, and OCP CRUD forms to reflect the type change | 🚧 In Progress |
| 1.4 | Add a schema regression test that fails if any tenant_id column deviates from VARCHAR(36) | 📋 Planned |

### Pending Brownfield Findings

| Finding | Severity | Status | Description |
|---|---|---|---|
| M1 | Medium | 🚧 In Progress | Pending remediation from brownfield scan |
| M2 | Medium | 🚧 In Progress | Pending remediation from brownfield scan |
| L3 | Low | 🚧 In Progress | Pending remediation from brownfield scan |

### Acceptance Criteria

- [ ] docker compose config renders without warnings when .env.example is copied to .env.
- [ ] psql \d sip_trunk_did_mappings shows tenant_id | character varying(36).
- [ ] Integration test test_sip_trunk_inbound.py passes after schema change.
- [ ] Brownfield scan re-run reports zero schema inconsistencies.

---

## Stage 2 — VEX Generation in CI

**Goal:** Integrate software-bill-of-materials (SBOM) and Vulnerability Exploitability eXchange (VEX) generation into the CI pipeline.

**Dependencies:** Stage 1

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 2.1 | Add Syft or Trivy SBOM generation step to .github/workflows/ci.yml for all project-owned Docker images | 📋 Planned |
| 2.2 | Generate CycloneDX SBOMs and attach them as CI artifacts | 📋 Planned |
| 2.3 | Add VEX document generation that marks known non-exploitable findings (e.g., db_mysql absence is by design) | 📋 Planned |
| 2.4 | Store SBOM/VEX artifacts in reports/ with versioned filenames | 📋 Planned |

### Acceptance Criteria

- [ ] CI run on master produces reports/sbom-opensips-{sha}.json and reports/vex-tsisip-{sha}.json.
- [ ] VEX document correctly flags the intentional absence of db_mysql and sanity as design decisions.
- [ ] A new CI job vex-validate passes, ensuring VEX is parseable by cyclonedx-cli.

---

## Stage 3 — Supply Chain Determinism & Image Tagging

**Goal:** Replace floating :latest tags with immutable references and establish a release/rollback manifest.

**Dependencies:** Stage 2

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 3.1 | Pin all FROM images in Dockerfiles to SHA256 digests | 📋 Planned |
| 3.2 | Introduce a Makefile target make release-tag that generates semver tags (vYYYY.MM.DD-N) and manifests | 📋 Planned |
| 3.3 | Update docker-compose.prod.yml and docker-compose.vps.yml to use the release manifest instead of :latest | 📋 Planned |
| 3.4 | Create deploy/scripts/rollback.sh that reads the manifest and re-deploys the previous tag | 📋 Planned |
| 3.5 | Document the release/rollback procedure in deploy/README.md | 📋 Planned |

### Acceptance Criteria

- [ ] docker buildx imagetools inspect on any released image resolves to a pinned digest.
- [ ] make release-tag produces release-manifest.json with image-to-digest mappings.
- [ ] Rollback script successfully reverts the VPS stack to the previous manifest in under 60 seconds.
- [ ] No :latest references remain in production-facing Compose files.

---

## Stage 4 — Scheduled Job Observability & Validation

**Goal:** Verify and monitor the automated backup/purge/validate cron pipeline.

**Dependencies:** Stage 3

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 4.1 | Add a jobs/ directory with timestamped logs for backup (02:00 UTC), purge (03:00 UTC), and validate (04:00 UTC) | 📋 Planned |
| 4.2 | Expose cron job health via the backup metrics exporter (backup_job_last_success, backup_job_last_duration) | 📋 Planned |
| 4.3 | Create tests/integration/test_backup_cron.py that simulates a cron window and asserts metrics emission | 📋 Planned |
| 4.4 | Add an Alertmanager rule that fires if any backup job has not succeeded within 25 hours | 📋 Planned |

### Acceptance Criteria

- [ ] After 24 hours of runtime, /backup/logs/ contains timestamped stdout/stderr for each job.
- [ ] curl http://backup:9101/metrics returns backup_job_last_success{job="daily"} > 0.
- [ ] Integration test passes in CI using a mocked cron trigger.
- [ ] Alertmanager rule evaluates without syntax errors.

---

## Stage 5 — Prometheus/Grafana Enablement on VPS

**Goal:** Re-enable the full observability stack on the VPS-lite profile when port/memory policy allows.

**Dependencies:** Stage 4

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 5.1 | Create a docker-compose.monitoring.yml overlay with Prometheus, Grafana, Alertmanager, opensips-exporter, and anomaly-detector | 📋 Planned |
| 5.2 | Configure Prometheus to scrape all metrics_host-network exporters (backup, certbot-exporter) | 📋 Planned |
| 5.3 | Import the TSiSIP Grafana dashboards from docker/grafana/ | 📋 Planned |
| 5.4 | Add a make monitoring-up target that starts the overlay without touching the core stack | 📋 Planned |
| 5.5 | Document memory headroom requirements (~1.5GB additional) and port allocation | 📋 Planned |

### Acceptance Criteria

- [ ] make monitoring-up brings up 5 additional services that show healthy in docker compose ps.
- [ ] Prometheus targets page shows opensips, backup, certbot-exporter as UP.
- [ ] Grafana dashboard TSiSIP SIP Overview displays live dispatcher set metrics.
- [ ] Anomaly-detector container emits a tsisip_anomaly_score metric within 5 minutes of startup.

---

## Stage 6 — SIP Public Exposure & End-to-End Call Validation

**Goal:** Open the SIP edge to the public internet and validate a complete call flow.

**Dependencies:** Stage 5

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 6.1 | Coordinate upstream ACL/NAT/Tailscale policy to allow 5060/udp, 5060/tcp, and 5061/tcp to the VPS host | 📋 Planned |
| 6.2 | Run sipsak and external SIP probe from outside the VPS network to confirm reachability | 📋 Planned |
| 6.3 | Execute the full integration test suite (test_end_to_end_call.py, test_multi_tenant_routing.py, test_webrtc_support.py, test_tls_srtp.py) | 📋 Planned |
| 6.4 | Record call flow evidence (pcaps, CDRs, RTPengine stats) in evidence/phase6/ | 📋 Planned |

### Acceptance Criteria

- [ ] External sipsak probe returns SIP/2.0 200 OK.
- [ ] External INVITE without auth returns SIP/2.0 407 Proxy Authentication Required.
- [ ] Authenticated INVITE from an external SIP client routes to the correct Asterisk backend.
- [ ] tcpdump on the VPS shows RTP flowing through RTPengine ports without exposing Asterisk IPs.
- [ ] CDR viewer in OCP shows the test call with correct tenant attribution.

---

## Stage 7 — Advanced Trunk Operations & DID Management

**Goal:** Extend OCP with full trunk health dashboards and tenant-scoped DID self-service.

**Dependencies:** Stage 6

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 7.1 | Add trunk-health.php to OCP: real-time UAC registration status, provider latency, and failover state | 📋 Planned |
| 7.2 | Add did-management.php to OCP: tenant-scoped DID mapping CRUD with validation against sip_trunk_did_mappings | 📋 Planned |
| 7.3 | Implement DID routing validation in OpenSIPS config: inbound trunk call -> DID lookup -> tenant resolution -> Asterisk backend | 📋 Planned |
| 7.4 | Add tests/integration/test_sip_trunk_did_routing.py with DID-to-tenant routing scenarios | 📋 Planned |

### Acceptance Criteria

- [ ] OCP trunk-health.php displays green/red status for each trunk provider with last-seen timestamp.
- [ ] OCP did-management.php allows an admin to add/remove DID mappings and shows tenant isolation.
- [ ] OpenSIPS routes an INVITE from a trunk provider to the correct Asterisk backend based on the DID.
- [ ] Integration test covers: DID match, DID not found (404), DID tenant mismatch (403).

---

## Stage 8 — Real TLS/rClone Offsite & PITR Validation

**Goal:** Replace dummy TLS certificates and test rClone configs with production-ready offsite backup and point-in-time recovery.

**Dependencies:** Stage 4, Stage 6

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 8.1 | Configure real MinIO/S3-compatible offsite target in secrets/rclone.conf (never committed) | 📋 Planned |
| 8.2 | Update docker/backup/replicate.sh to use the real remote and add bandwidth limiting | 📋 Planned |
| 8.3 | Execute a manual PITR restore to a fresh PostgreSQL container and verify data consistency | 📋 Planned |
| 8.4 | Document the PITR runbook with time-to-recovery (TTR) metrics | 📋 Planned |
| 8.5 | Add tests/integration/test_backup_pitr.py that exercises PITR against a local MinIO container | 📋 Planned |

### Acceptance Criteria

- [ ] rclone ls remote:tsisip-backups lists encrypted backup files after the next scheduled backup window.
- [ ] PITR restore from a known WAL point succeeds and passes pg_dump checksum comparison.
- [ ] Documented TTR is under 30 minutes for a 10GB database.
- [ ] CI integration test passes against a local MinIO instance.

---

## Stage 9 — LGPD Compliance Automation & Data Retention

**Goal:** Automate LGPD-compliant data retention, anonymization, and audit export.

**Dependencies:** Stage 7, Stage 8

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 9.1 | Implement web/cli/purge-cdr.php: anonymize CDRs older than retention period instead of deleting (preserve billing aggregates) | 📋 Planned |
| 9.2 | Implement web/cli/export-audit-lgpd.php: generate a machine-readable export of all audit events for a given subscriber (right of access) | 📋 Planned |
| 9.3 | Add LGPD_RETENTION_DAYS and LGPD_ANONYMIZE_AFTER_DAYS to .env.example | 📋 Planned |
| 9.4 | Update backup purge policy to respect LGPD retention windows | 📋 Planned |
| 9.5 | Create tests/integration/test_lgpd_compliance.py validating access export and anonymization | 📋 Planned |

### Acceptance Criteria

- [ ] Running php web/cli/purge-cdr.php --dry-run shows CDRs eligible for anonymization without altering data.
- [ ] Running php web/cli/export-audit-lgpd.php --subscriber=test@example.com produces a JSON file with all audit events.
- [ ] Anonymized CDRs retain call_duration and cost but replace from_user and to_user with hashed values.
- [ ] Backup purge script skips WAL segments needed for LGPD retention window recovery.

---

## Stage 10 — Operational Runbook Automation & Incident Response

**Goal:** Convert manual operator procedures into automated, testable runbooks with health-gated execution.

**Dependencies:** Stages 1–9

**Overall Status:** 📋 Planned

### Deliverables

| # | Deliverable | Status |
|---|---|---|
| 10.1 | Create scripts/runbook/ directory with executable runbooks | 📋 Planned |
| 10.2 | failover-pbx.sh — mark a dispatcher destination as inactive and verify traffic shifts | 📋 Planned |
| 10.3 | rotate-tls-manual.sh — trigger certbot dry-run, then live rotation with rollback on failure | 📋 Planned |
| 10.4 | scale-asterisk.sh — add a new Asterisk backend to dispatcher set and verify with health probe | 📋 Planned |
| 10.5 | Each runbook produces a JSON evidence artifact in evidence/runbook/{timestamp}/ | 📋 Planned |
| 10.6 | Add tests/integration/test_runbook_failover.py that simulates a PBX failure and validates automated failover | 📋 Planned |
| 10.7 | Document runbook execution in docs/TSiSIP-OPERATOR-RUNBOOK.md | 📋 Planned |

### Acceptance Criteria

- [ ] bash scripts/runbook/failover-pbx.sh asterisk-pbx-1 sets dispatcher state to 1 and traffic routes to asterisk-pbx-2.
- [ ] bash scripts/runbook/rotate-tls-manual.sh completes with new certificate and zero-downtime OpenSIPS reload.
- [ ] bash scripts/runbook/scale-asterisk.sh <new-pbx-ip> adds the new IP to dispatcher set 1 with state=0.
- [ ] Each runbook produces a valid JSON evidence file with start_time, end_time, steps, and result.

---

## Summary Dashboard

| Stage | Focus | Status | Progress |
|-------|-------|--------|----------|
| Completed | Foundation (001–024) | ✅ | 18/18 capabilities |
| 1 | Brownfield gap closure | 🚧 | In Progress |
| 2 | VEX in CI | 📋 | Planned |
| 3 | Supply chain determinism | 📋 | Planned |
| 4 | Cron observability | 📋 | Planned |
| 5 | Monitoring enablement | 📋 | Planned |
| 6 | SIP public exposure | 📋 | Planned |
| 7 | Trunk/DID management | 📋 | Planned |
| 8 | Offsite backup + PITR | 📋 | Planned |
| 9 | LGPD compliance | 📋 | Planned |
| 10 | Runbook automation | 📋 | Planned |

**VPS Status:** ✅ Healthy — 10 services running, OpenSIPS 3.6.6 on production
