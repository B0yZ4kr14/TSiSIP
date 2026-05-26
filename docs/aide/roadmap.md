# TSiSIP — Development Roadmap

> Generated from docs/aide/vision.md
> Last updated: 2026-05-26
>
> <!-- docguard:last-reviewed 2026-05-26 -->
> <!-- docguard:ignore freshness -->

---

## Overview

This roadmap defines the staged delivery plan for TSiSIP from its current production-hardened baseline toward full operational maturity. Each stage is designed to be demonstrable, testable, and deployable within approximately one week.

**Current baseline:** Features 001–024 are implemented. The VPS-lite production stack (10 services, ~7.5GB RAM) is healthy. Remaining work centers on brownfield gap closure, upstream SIP exposure, observability enablement, and operational automation.

---

## Stage 1 — Brownfield Gap Closure & Schema Hardening

**Goal:** Resolve all outstanding medium/low findings from the 2026-05-19 brownfield scan and enforce schema consistency.

**Dependencies:** None — can proceed in parallel with running VPS stack.

**Deliverables:**
- Add RTPENGINE_INTERNAL_IP to .env.example with documented default and override behavior.
- Migrate sip_trunk_did_mappings.tenant_id from UUID to VARCHAR(36) to align with the rest of the tenant ID schema.
- Update all DDL references, seed data, and OCP CRUD forms to reflect the type change.
- Add a schema regression test that fails if any tenant_id column deviates from VARCHAR(36).

**Acceptance Criteria:**
- [ ] docker compose config renders without warnings when .env.example is copied to .env.
- [ ] psql \d sip_trunk_did_mappings shows tenant_id | character varying(36).
- [ ] Integration test test_sip_trunk_inbound.py passes after schema change.
- [ ] Brownfield scan re-run reports zero schema inconsistencies.

---

## Stage 2 — VEX Generation in CI

**Goal:** Integrate software-bill-of-materials (SBOM) and Vulnerability Exploitability eXchange (VEX) generation into the CI pipeline.

**Dependencies:** Stage 1 (schema hardening must be stable so CI baseline is clean).

**Deliverables:**
- Add Syft or Trivy SBOM generation step to .github/workflows/ci.yml for all project-owned Docker images.
- Generate CycloneDX SBOMs and attach them as CI artifacts.
- Add VEX document generation that marks known non-exploitable findings (e.g., db_mysql absence is by design).
- Store SBOM/VEX artifacts in reports/ with versioned filenames.

**Acceptance Criteria:**
- [ ] CI run on master produces reports/sbom-opensips-{sha}.json and reports/vex-tsisip-{sha}.json.
- [ ] VEX document correctly flags the intentional absence of db_mysql and sanity as design decisions.
- [ ] A new CI job vex-validate passes, ensuring VEX is parseable by cyclonedx-cli.

---

## Stage 3 — Supply Chain Determinism & Image Tagging

**Goal:** Replace floating :latest tags with immutable references and establish a release/rollback manifest.

**Dependencies:** Stage 2 (CI artifact pipeline must be in place).

**Deliverables:**
- Pin all FROM images in Dockerfiles to SHA256 digests.
- Introduce a Makefile target make release-tag that generates semver tags (vYYYY.MM.DD-N) and manifests.
- Update docker-compose.prod.yml and docker-compose.vps.yml to use the release manifest instead of :latest.
- Create deploy/scripts/rollback.sh that reads the manifest and re-deploys the previous tag.
- Document the release/rollback procedure in deploy/README.md.

**Acceptance Criteria:**
- [ ] docker buildx imagetools inspect on any released image resolves to a pinned digest.
- [ ] make release-tag produces release-manifest.json with image-to-digest mappings.
- [ ] Rollback script successfully reverts the VPS stack to the previous manifest in under 60 seconds.
- [ ] No :latest references remain in production-facing Compose files.

---

## Stage 4 — Scheduled Job Observability & Validation

**Goal:** Verify and monitor the automated backup/purge/validate cron pipeline.

**Dependencies:** Stage 3 (deterministic images reduce variables during cron validation).

**Deliverables:**
- Add a jobs/ directory with timestamped logs for backup (02:00 UTC), purge (03:00 UTC), and validate (04:00 UTC).
- Expose cron job health via the backup metrics exporter (backup_job_last_success, backup_job_last_duration).
- Create tests/integration/test_backup_cron.py that simulates a cron window and asserts metrics emission.
- Add an Alertmanager rule that fires if any backup job has not succeeded within 25 hours.

**Acceptance Criteria:**
- [ ] After 24 hours of runtime, /backup/logs/ contains timestamped stdout/stderr for each job.
- [ ] curl http://backup:9101/metrics returns backup_job_last_success{job="daily"} > 0.
- [ ] Integration test passes in CI using a mocked cron trigger.
- [ ] Alertmanager rule evaluates without syntax errors.

---

## Stage 5 — Prometheus/Grafana Enablement on VPS

**Goal:** Re-enable the full observability stack on the VPS-lite profile when port/memory policy allows.

**Dependencies:** Stage 4 (backup job stability proven before adding memory pressure).

**Deliverables:**
- Create a docker-compose.monitoring.yml overlay with Prometheus, Grafana, Alertmanager, opensips-exporter, and anomaly-detector.
- Configure Prometheus to scrape all metrics_host-network exporters (backup, certbot-exporter).
- Import the TSiSIP Grafana dashboards from docker/grafana/.
- Add a make monitoring-up target that starts the overlay without touching the core stack.
- Document memory headroom requirements (~1.5GB additional) and port allocation.

**Acceptance Criteria:**
- [ ] make monitoring-up brings up 5 additional services that show healthy in docker compose ps.
- [ ] Prometheus targets page shows opensips, backup, certbot-exporter as UP.
- [ ] Grafana dashboard TSiSIP SIP Overview displays live dispatcher set metrics.
- [ ] Anomaly-detector container emits a tsisip_anomaly_score metric within 5 minutes of startup.

---

## Stage 6 — SIP Public Exposure & End-to-End Call Validation

**Goal:** Open the SIP edge to the public internet and validate a complete call flow.

**Dependencies:** Stage 5 (monitoring must be in place before exposing public traffic).

**Deliverables:**
- Coordinate upstream ACL/NAT/Tailscale policy to allow 5060/udp, 5060/tcp, and 5061/tcp to the VPS host.
- Run sipsak and external SIP probe from outside the VPS network to confirm reachability.
- Execute the full integration test suite:
  - test_end_to_end_call.py
  - test_multi_tenant_routing.py
  - test_webrtc_support.py
  - test_tls_srtp.py
- Record call flow evidence (pcaps, CDRs, RTPengine stats) in evidence/phase6/.

**Acceptance Criteria:**
- [ ] External sipsak probe returns SIP/2.0 200 OK.
- [ ] External INVITE without auth returns SIP/2.0 407 Proxy Authentication Required.
- [ ] Authenticated INVITE from an external SIP client routes to the correct Asterisk backend.
- [ ] tcpdump on the VPS shows RTP flowing through RTPengine ports without exposing Asterisk IPs.
- [ ] CDR viewer in OCP shows the test call with correct tenant attribution.

---

## Stage 7 — Advanced Trunk Operations & DID Management

**Goal:** Extend OCP with full trunk health dashboards and tenant-scoped DID self-service.

**Dependencies:** Stage 6 (SIP public exposure validated; real trunk traffic possible).

**Deliverables:**
- Add trunk-health.php to OCP: real-time UAC registration status, provider latency, and failover state.
- Add did-management.php to OCP: tenant-scoped DID mapping CRUD with validation against sip_trunk_did_mappings.
- Implement DID routing validation in OpenSIPS config: inbound trunk call → DID lookup → tenant resolution → Asterisk backend.
- Add tests/integration/test_sip_trunk_did_routing.py with DID-to-tenant routing scenarios.

**Acceptance Criteria:**
- [ ] OCP trunk-health.php displays green/red status for each trunk provider with last-seen timestamp.
- [ ] OCP did-management.php allows an admin to add/remove DID mappings and shows tenant isolation.
- [ ] OpenSIPS routes an INVITE from a trunk provider to the correct Asterisk backend based on the DID.
- [ ] Integration test covers: DID match, DID not found (404), DID tenant mismatch (403).

---

## Stage 8 — Real TLS/rClone Offsite & PITR Validation

**Goal:** Replace dummy TLS certificates and test rClone configs with production-ready offsite backup and point-in-time recovery.

**Dependencies:** Stage 4 (backup cron proven); Stage 6 (public traffic enables real cert validation).

**Deliverables:**
- Configure real MinIO/S3-compatible offsite target in secrets/rclone.conf (never committed).
- Update docker/backup/replicate.sh to use the real remote and add bandwidth limiting.
- Execute a manual PITR restore to a fresh PostgreSQL container and verify data consistency.
- Document the PITR runbook with time-to-recovery (TTR) metrics.
- Add tests/integration/test_backup_pitr.py that exercises PITR against a local MinIO container.

**Acceptance Criteria:**
- [ ] rclone ls remote:tsisip-backups lists encrypted backup files after the next scheduled backup window.
- [ ] PITR restore from a known WAL point succeeds and passes pg_dump checksum comparison.
- [ ] Documented TTR is under 30 minutes for a 10GB database.
- [ ] CI integration test passes against a local MinIO instance.

---

## Stage 9 — LGPD Compliance Automation & Data Retention

**Goal:** Automate LGPD-compliant data retention, anonymization, and audit export.

**Dependencies:** Stage 7 (audit logs and CDRs are the primary retention targets); Stage 8 (backup retention policy must align with LGPD storage limits).

**Deliverables:**
- Implement web/cli/purge-cdr.php: anonymize CDRs older than retention period instead of deleting (preserve billing aggregates).
- Implement web/cli/export-audit-lgpd.php: generate a machine-readable export of all audit events for a given subscriber (right of access).
- Add LGPD_RETENTION_DAYS and LGPD_ANONYMIZE_AFTER_DAYS to .env.example.
- Update backup purge policy to respect LGPD retention windows.
- Create tests/integration/test_lgpd_compliance.py validating access export and anonymization.

**Acceptance Criteria:**
- [ ] Running php web/cli/purge-cdr.php --dry-run shows CDRs eligible for anonymization without altering data.
- [ ] Running php web/cli/export-audit-lgpd.php --subscriber=test@example.com produces a JSON file with all audit events.
- [ ] Anonymized CDRs retain call_duration and cost but replace from_user and to_user with hashed values.
- [ ] Backup purge script skips WAL segments needed for LGPD retention window recovery.

---

## Stage 10 — Operational Runbook Automation & Incident Response

**Goal:** Convert manual operator procedures into automated, testable runbooks with health-gated execution.

**Dependencies:** Stages 1–9 (all preceding operational features must be stable).

**Deliverables:**
- Create scripts/runbook/ directory with executable runbooks:
  - failover-pbx.sh — mark a dispatcher destination as inactive and verify traffic shifts.
  - rotate-tls-manual.sh — trigger certbot dry-run, then live rotation with rollback on failure.
  - scale-asterisk.sh — add a new Asterisk backend to dispatcher set and verify with health probe.
- Each runbook produces a JSON evidence artifact in evidence/runbook/{timestamp}/.
- Add tests/integration/test_runbook_failover.py that simulates a PBX failure and validates automated failover.
- Document runbook execution in docs/TSiSIP-OPERATOR-RUNBOOK.md.

**Acceptance Criteria:**
- [ ] bash scripts/runbook/failover-pbx.sh asterisk-pbx-1 sets dispatcher state to 1 and traffic routes to asterisk-pbx-2.
- [ ] bash scripts/runbook/rotate-tls-manual.sh completes with new certificate and zero-downtime OpenSIPS reload.
- [ ] bash scripts/runbook/scale-asterisk.sh <new-pbx-ip> adds the new IP to dispatcher set 1 with state=0.
- [ ] Each runbook produces a valid JSON evidence file with start_time, end_time, steps, and result.

---

## Summary

| Stage | Focus | Duration | Key Deliverable |
|-------|-------|----------|-----------------|
| 1 | Brownfield gap closure | 3–4 days | Schema regression test passes |
| 2 | VEX in CI | 3–4 days | SBOM + VEX artifacts on every CI run |
| 3 | Supply chain determinism | 4–5 days | Immutable release manifest + rollback script |
| 4 | Cron observability | 3–4 days | Backup job metrics + alert rule |
| 5 | Monitoring enablement | 4–5 days | Full Prometheus/Grafana overlay on VPS |
| 6 | SIP public exposure | 4–5 days | End-to-end external call validated |
| 7 | Trunk/DID management | 5–6 days | OCP DID CRUD + DID routing integration test |
| 8 | Offsite backup + PITR | 5–6 days | Real offsite replication + PITR CI test |
| 9 | LGPD compliance | 5–6 days | Automated CDR anonymization + audit export |
| 10 | Runbook automation | 5–6 days | Executable runbooks with JSON evidence |

**Estimated total duration:** 10–12 weeks (sequential stages; some stages 1–4 can be partially parallelized).

**Exit criteria for the roadmap:**
- All brownfield scan findings resolved.
- SIP edge handles public traffic with monitoring, alerting, and automated failover.
- Backup and PITR are tested against real offsite targets.
- LGPD compliance workflows are automated and tested.
- Operator runbooks are executable and produce evidence artifacts.
