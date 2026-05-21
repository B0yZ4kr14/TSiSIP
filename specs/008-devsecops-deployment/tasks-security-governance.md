# Security Governance Tasks: Feature 008 — DevSecOps Deployment Automation

**Preset**: Security Governance (speckit-tasks)  
**Generated**: 2026-05-19  
**Updated**: 2026-05-19  
**Applies to**: Feature 008 spec, plan, checklists, and live TSiAPP deployment  
**Purpose**: Convert MSL applicability, security obligations, and evidence-production needs into explicit, trackable tasks. Nothing secure-development-related is left as an undocumented assumption.

---

## Governance Principles

1. **Every security claim must be falsifiable** — each task produces verifiable evidence.
2. **Evidence lives in `docs/security/`** — not in transient CI logs or operator memory.
3. **MSL applicability is justified, not assumed** — we document why each control is in-scope.
4. **Residual risk is declared** — gaps that are accepted-by-design are written down.

---

## Phase SG-1 — MSL Applicability & Justification

### [complete] SG1.1: Document MSL applicability matrix for TSiAPP deployment
**Description**: Create `docs/security/008-MSL-applicability-justification.md`. Map each Minimum Security Level (MSL) control area to the TSiSIP architecture and declare applicability (applicable / partially applicable / not applicable / out-of-scope) with rationale. Cover: Identity & Access Management, Network Security, Data Protection, Logging & Monitoring, Vulnerability Management, Incident Response, Business Continuity.
**Phase**: SG-1  
**Depends on**: —  
**Parallel**: No  
**Acceptance**: Document reviewed and signed off by at least one independent reviewer. File exists and contains no `[TBD]` placeholders.  
**Evidence**: `docs/security/008-MSL-applicability-justification.md`

### [complete] SG1.2: Declare residual risk register for accepted gaps
**Description**: In the same MSL document, add a Residual Risk Register section listing every security gap that is accepted-by-design, with: risk description, likelihood, impact, mitigating controls, risk owner, review date. Initial entries: (a) backup metrics exporter main process runs as root due to Debian cron requirement, (b) Prometheus/Grafana full observability stack excluded from VPS-lite profile, (c) dummy TLS certificates in CI until real certs are provisioned.
**Phase**: SG-1  
**Depends on**: SG1.1  
**Parallel**: No  
**Acceptance**: Each risk has a mitigating control and a review date no more than 90 days in the future.  
**Evidence**: `docs/security/008-MSL-applicability-justification.md` (Section 3)

---

## Phase SG-2 — Evidence Production (Infrastructure Quality Remediation)

### [complete] SG2.1: Harmonize backup metrics exporter binding across all compose profiles
**Description**: Resolve the divergence between `docker-compose.vps.yml` (binds loopback port 9101) and `docker-compose.yml` / `docker-compose.prod.yml` (uses container-only expose with no host-published port). The VPS profile binds to loopback because the backup metrics exporter is scraped by a host-level Prometheus agent (if present); the full profiles use container-only because Prometheus is containerized on the same Docker network. Document this architectural decision in `docs/security/008-network-binding-decisions.md` and ensure all three files are consistent with their intended deployment context.
**Phase**: SG-2  
**Depends on**: —  
**Parallel**: No  
**Acceptance**: `infra-quality.md` Network Isolation Verification item for backup metrics exporter is marked complete with a permalink to the decision document.  
**Evidence**: `docs/security/008-network-binding-decisions.md`

### [complete] SG2.2: Make nginx config validation runnable in CI without host nginx binary
**Description**: The current `deploy/validate.sh` skips nginx syntax check when the binary is missing. Add a CI-native validation path: create a lightweight container (`docker run --rm -v $(pwd)/deploy/nginx:/etc/nginx:ro nginx:alpine nginx -t`) that validates the nginx configuration without requiring the binary on the host. Update `deploy/validate.sh` to prefer the container method when the host binary is absent. Update CI workflow to run this check.
**Phase**: SG-2  
**Depends on**: —  
**Parallel**: No  
**Acceptance**: `infra-quality.md` nginx syntax check item is marked complete. CI workflow fails on invalid nginx config.  
**Evidence**: `deploy/validate.sh` (lines 95–115)

### [complete] SG2.3: Make Ansible syntax-check runnable in CI without host ansible binary
**Description**: The current `deploy/validate.sh` skips ansible-playbook syntax check when the binary is missing. Add a CI-native validation path: create a Docker-based syntax check using a lightweight ansible image, or add a GitHub Actions job that runs on an Ubuntu runner with ansible and community.general pre-installed. Update `deploy/validate.sh` to detect CI environment and run the container-based check when the host binary is absent.
**Phase**: SG-2  
**Depends on**: SG2.2  
**Parallel**: No  
**Acceptance**: `infra-quality.md` ansible syntax-check item is marked complete. CI workflow fails on invalid Ansible syntax.  
**Evidence**: `deploy/validate.sh` (lines 71–89)

---

## Phase SG-3 — Evidence Production (Security Controls)

### [pending] SG3.1: Obtain and archive formal TLS grade evidence
**Description**: Once real TLS certificates are deployed on TSiAPP (via Feature 015 automation or manual provisioning), run a Qualys SSL Labs scan against the public endpoint. Save the full report (PDF or HTML) to `docs/security/evidence/008-ssl-labs-grade-<date>.html`. Target grade: A+. If grade is below A+, create remediation tasks. Update `specs/008-devsecops-deployment/spec.md` status to reflect completion.
**Phase**: SG-3  
**Depends on**: SG2.2 (nginx config must be valid)  
**Parallel**: No  
**Acceptance**: Evidence file exists in repository. Grade is documented in `docs/security/008-security-evidence-index.md`.  
**Status**: BLOCKED — awaiting real TLS certificate deployment on TSiAPP public endpoint.

### [complete] SG3.2: Produce container image CVE scan evidence
**Description**: The CI already runs Trivy with HIGH/CRITICAL severity and exit-code 1. Add an evidence-production step: after each Trivy scan in the deploy workflow, upload the JSON report as a build artifact and copy the latest report to `docs/security/evidence/008-trivy-scan-latest.json` on every release tag. Document the scan cadence (every image build) and threshold (HIGH/CRITICAL = blocking) in `docs/security/008-vulnerability-management.md`.
**Phase**: SG-3  
**Depends on**: —  
**Parallel**: Yes (with SG3.1)  
**Acceptance**: CI workflow uploads Trivy JSON artifact. `docs/security/008-security-evidence-index.md` references the latest scan.  
**Evidence**: `docs/security/008-vulnerability-management.md`

### [complete] SG3.3: Produce network isolation verification evidence
**Description**: Create an executable script `scripts/verify-network-isolation.sh` that programmatically verifies: (a) sip_internal and db_internal networks are marked internal: true in all compose files, (b) Asterisk and PostgreSQL have no ports stanza, (c) RTPengine control socket binds to a non-wildcard address. The script exits 0 on pass, 1 on fail with specific violation. Run it in CI and archive the output as evidence.
**Phase**: SG-3  
**Depends on**: SG2.1  
**Parallel**: Yes (with SG3.1, SG3.2)  
**Acceptance**: Script passes in CI. Output archived in `docs/security/evidence/008-network-isolation-<date>.txt`.  
**Evidence**: `scripts/verify-network-isolation.sh`

### [complete] SG3.4: Produce secret-management audit evidence
**Description**: Create an executable script `scripts/verify-secrets-audit.sh` that verifies: (a) the secrets directory and env files are in gitignore, (b) no file in the secrets directory is tracked by git, (c) all Docker Compose secrets mounts reference files under the secrets directory, (d) the auth credential file is exactly 32 bytes, (e) no plaintext password columns exist in database initialization SQL. Run it in CI and archive the output.
**Phase**: SG-3  
**Depends on**: —  
**Parallel**: Yes (with SG3.1, SG3.2, SG3.3)  
**Acceptance**: Script passes in CI. Output archived in `docs/security/evidence/008-secrets-audit-<date>.txt`.  
**Evidence**: `scripts/verify-secrets-audit.sh`

### [complete] SG3.5: Produce Nginx TLS configuration evidence
**Description**: Create an executable script `scripts/verify-nginx-tls.sh` that extracts the live nginx configuration and verifies: (a) TLSv1.0/1.1 are disabled, (b) strong cipher suite is present, (c) HSTS header includes max-age of at least 63072000 with includeSubDomains and preload, (d) OCSP stapling is enabled, (e) rate limiting is active for the OCP path. For CI, run against the configuration template and validate regex matches. Archive output.
**Phase**: SG-3  
**Depends on**: SG2.2  
**Parallel**: Yes (with SG3.1–SG3.4)  
**Acceptance**: Script passes in CI. Output archived in `docs/security/evidence/008-nginx-tls-<date>.txt`.  
**Evidence**: `scripts/verify-nginx-tls.sh`

### [complete] SG3.6: Produce health-check validation evidence
**Description**: Create an executable script `scripts/verify-health-checks.sh` that verifies every service in all compose files has a healthcheck stanza with non-trivial test (not just `true`). For services with custom scripts (OpenSIPS, RTPengine, Asterisk), verify the script file exists in the repository. Archive output.
**Phase**: SG-3  
**Depends on**: —  
**Parallel**: Yes (with SG3.1–SG3.5)  
**Acceptance**: Script passes in CI. Output archived in `docs/security/evidence/008-health-checks-<date>.txt`.  
**Evidence**: `scripts/verify-health-checks.sh`

---

## Phase SG-4 — Operational Security Tasks

### [complete] SG4.1: Implement 90-day secret rotation audit trail
**Description**: Add a script `scripts/secret-age-audit.sh` that checks the mtime of every file in the secrets directory and warns if any secret is older than 90 days. Integrate into `scripts/ci-scan.sh` as a non-blocking warning. Document rotation procedures in `docs/security/008-secret-rotation-procedures.md`.
**Phase**: SG-4  
**Depends on**: SG3.4  
**Parallel**: No  
**Acceptance**: CI emits a warning when any secret file is >90 days old. Rotation procedure document exists.  
**Evidence**: `scripts/secret-age-audit.sh`, `docs/security/008-secret-rotation-procedures.md`

### [complete] SG4.2: Complete deterministic image pinning across all services
**Description**: Ensure every image reference in all compose files uses a deterministic tag (git SHA or semantic version) or a SHA256 digest. The certbot and tailscale-cert local builds currently use a latest fallback; replace with explicit tag requirement or document why local builds require latest (ephemeral CI builds without tags). Update `docs/security/008-image-pinning-policy.md`.
**Phase**: SG-4  
**Depends on**: —  
**Parallel**: Yes (with SG4.1)  
**Acceptance**: No `:latest` in any production compose file except where explicitly justified and documented.  
**Evidence**: `docs/security/008-image-pinning-policy.md`

### [complete] SG4.3: Document upstream SIP port exposure decision
**Description**: The spec status notes "upstream SIP edge exposure remains pending". Document the decision and timeline for exposing SIP signaling ports on TSiAPP's public interface. If exposure is deferred, document the rationale (e.g., awaiting provider IP whitelist, awaiting TLS cert automation completion) and the conditions for activation. File: `docs/security/008-sip-exposure-decision.md`.
**Phase**: SG-4  
**Depends on**: —  
**Parallel**: Yes (with SG4.1, SG4.2)  
**Acceptance**: Decision document exists, is dated, and has a review-by date.  
**Evidence**: `docs/security/008-sip-exposure-decision.md`

### [complete] SG4.4: Create security incident response runbook
**Description**: Document the step-by-step response for: (a) suspected secret compromise, (b) container escape / privilege escalation, (c) SIP abuse / toll fraud, (d) DDoS against Nginx or SIP ports. Include: detection signals (which logs/alerts), containment steps, evidence preservation, communication plan, recovery steps. File: `docs/security/008-incident-response-runbook.md`.
**Phase**: SG-4  
**Depends on**: SG1.1, SG1.2  
**Parallel**: Yes (with SG4.1–SG4.3)  
**Acceptance**: Runbook reviewed by at least one independent reviewer. Contains no `[TBD]` placeholders.  
**Evidence**: `docs/security/008-incident-response-runbook.md`

---

## Phase SG-5 — Evidence Index & Finalization

### [complete] SG5.1: Create security evidence index
**Description**: Create `docs/security/008-security-evidence-index.md` as the canonical inventory of all security evidence for Feature 008. It must list every evidence artifact, its location, date produced, producing task, expiration date (if applicable), and next review date. Auto-generate a summary table.
**Phase**: SG-5  
**Depends on**: SG3.1–SG3.6, SG4.1–SG4.4  
**Parallel**: No  
**Acceptance**: Index is complete, has no missing references, and is committed to the repository.  
**Evidence**: `docs/security/008-security-evidence-index.md`

### [complete] SG5.2: Update Feature 008 spec status
**Description**: Update `specs/008-devsecops-deployment/spec.md` status field to reflect: (a) which SG tasks are complete, (b) which are pending and why, (c) next review date. Ensure the spec no longer says "formal public TLS grade evidence remain pending" once SG3.1 is complete.
**Phase**: SG-5  
**Depends on**: SG5.1  
**Parallel**: No  
**Acceptance**: Spec status is accurate and dated.  
**Evidence**: `specs/008-devsecops-deployment/spec.md`

### [complete] SG5.3: Final security governance sign-off
**Description**: Perform a final review of all SG-phase deliverables. Run `scripts/ci-scan.sh` and confirm zero hard failures. Confirm all evidence files are present in `docs/security/evidence/`. Confirm `docs/security/008-security-evidence-index.md` is current. Create a signed-off statement in the evidence index.
**Phase**: SG-5  
**Depends on**: SG5.2  
**Parallel**: No  
**Acceptance**: Zero hard failures in CI scan. Evidence index is current and contains a sign-off statement with date and reviewer initials.  
**Evidence**: `docs/security/008-security-evidence-index.md` (Section 3)

---

## Task Dependency Graph

```
SG1.1 -> SG1.2
SG1.1, SG1.2 -> SG4.4

SG2.1 -> SG3.3
SG2.2 -> SG3.1, SG3.5
SG2.3 -> (CI workflow enhancement)

SG3.1 through SG3.6 -> SG5.1
SG4.1 through SG4.4 -> SG5.1
SG5.1 -> SG5.2 -> SG5.3
```

## Evidence Archive Structure

```
docs/security/
  008-MSL-applicability-justification.md
  008-security-evidence-index.md
  008-compliance-evidence-checklist.md
  008-network-binding-decisions.md
  008-vulnerability-management.md
  008-secret-rotation-procedures.md
  008-image-pinning-policy.md
  008-sip-exposure-decision.md
  008-incident-response-runbook.md
  evidence/
    008-ssl-labs-grade-<date>.html        (pending SG3.1)
    008-trivy-scan-latest.json            (produced by CI)
    008-network-isolation-<date>.txt      (produced by verify-network-isolation.sh)
    008-secrets-audit-<date>.txt          (produced by verify-secrets-audit.sh)
    008-nginx-tls-<date>.txt              (produced by verify-nginx-tls.sh)
    008-health-checks-<date>.txt          (produced by verify-health-checks.sh)
```

## Quick Reference: Open Items from infra-quality.md

| Item | Checklist Section | SG Task | Status |
|---|---|---|---|
| Backup metrics exporter binding | Network Isolation | SG2.1 | complete |
| nginx syntax validation | Health Check Validation | SG2.2 | complete |
| Ansible syntax-check | Health Check Validation | SG2.3 | complete |
| SSL Labs A+ grade | Security Compliance SC-004b | SG3.1 | pending (blocked on cert deploy) |
| Image CVE scan artifact | Docker Image Security | SG3.2 | complete |
| Network isolation script | Network Isolation | SG3.3 | complete |
| Secrets audit script | Secret Management | SG3.4 | complete |
| Nginx TLS config script | Nginx TLS Configuration | SG3.5 | complete |
| Health check script | Health Check Validation | SG3.6 | complete |

---

**Completion Summary**: 17/18 tasks complete. 1 task (SG3.1) blocked on external certificate deployment.
