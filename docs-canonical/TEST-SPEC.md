# Test Specification

<!-- docguard:version 0.2.0 -->
<!-- docguard:status active -->
<!-- docguard:last-reviewed 2026-05-26 -->
<!-- docguard:generated false -->

> **Canonical Test Specification for TSiSIP.**

| Metadata | Value |
|----------|-------|
| **Status** | ![Status](https://img.shields.io/badge/status-active-brightgreen) |
| **Version** | `0.2.0` |
| **Last Updated** | 2026-05-26 |
| **Test Files Found** | 23 |

---

## Test Categories

| Category | Framework | Location | Run Command |
|----------|-----------|----------|-------------|
| Integration | Python (pytest) | `tests/integration/` | `pytest tests/integration/` |
| Integration | Bash | `tests/integration/` | `bash tests/integration/test-*.sh` |
| VPS Stabilization | Bash | `tests/vps-stabilization/` | `bash tests/vps-stabilization/test-*.sh` |
| Accessibility | Node.js (axe-core) | `tests/accessibility-audit.test.js` | `node tests/accessibility-audit.test.js` |
| Coexistence | Node.js | `tests/d3-jquery-coexistence.test.js` | `node tests/d3-jquery-coexistence.test.js` |
| Performance | sipp | `tests/performance/` | `bash tests/performance/run-load-tests.sh` |
| Visual Regression | BackstopJS | `tests/visual-regression/` | `backstop test --config=tests/visual-regression/backstop-config.js` |

## Coverage Rules

| Metric | Target | Current | Notes |
|--------|:------:|:-------:|-------|
| SIP Signaling | 100% | Manual + scripted | OPTIONS, REGISTER, INVITE, 407 auth flow |
| Trunk Routing | 100% | Bash scripts | Feature 017 end-to-end validation |
| OCP Accessibility | 100% | axe-core audit | WCAG 2.1 AA compliance |
| D3/jQuery Coexistence | 100% | Node.js test | No global `$` conflicts |
| VPS Health | 100% | Bash scripts | OpenSIPS, OCP, SIP, RED stack checks |
| TLS Rotation | 100% | Bash script | Certificate reload without restart |
| CDR/Billing | 100% | pytest | acc module + CDR viewer |

## Service-to-Test Map

| Source File / Feature | Unit Test | Integration Test | Status |
|-----------------------|-----------|-----------------|:------:|
| `opensips/opensips.cfg.tpl` | — | `test-vps-sip.sh` | ✅ |
| `db/init/02-tsisip-extensions.sql` | — | `test-feature-017.sh` | ✅ |
| `docker/rtpengine/` | — | `test-sip-call-flow.sh` | ✅ |
| `docker/asterisk/` | — | `test-sip-trunk.sh` | ✅ |
| `deploy/ansible/` | — | Manual VPS deploy | ⏳ |

**Note**: OCP frontend admin pages are tested via `test-ocp-audit.sh` and `accessibility-audit.test.js`.

## Critical User Journeys

| # | Journey | Test File | Status |
|---|---------|-----------|:------:|
| 1 | SIP OPTIONS probe returns 200 OK | `tests/vps-stabilization/test-vps-sip.sh` | ✅ |
| 2 | SIP REGISTER with digest auth succeeds | `tests/integration/test-sip-call-flow.sh` | ✅ |
| 3 | SIP INVITE triggers 407 Proxy-Authenticate | `tests/integration/test-sip-call-flow.sh` | ✅ |
| 4 | Trunk inbound DID routing (Feature 017) | `tests/vps-stabilization/test-feature-017.sh` | ✅ |
| 5 | Trunk outbound call via provider | `tests/integration/test_trunk_outbound_call.sh` | ✅ |
| 6 | TLS certificate auto-rotation | `tests/integration/test-tls-rotation.sh` | ✅ |
| 7 | OCP admin login + RBAC enforcement | `tests/vps-stabilization/test-red-ocp.sh` | ✅ |
| 8 | Audit log records auth events | `tests/integration/test-ocp-audit.sh` | ✅ |
| 9 | Rate limiting / DDoS protection | `tests/integration/test_ddos_protection.py` | ✅ |
| 10 | Backup & restore verification | `tests/integration/test_backup_rclone.py` | ✅ |
| 11 | Dispatcher failover on backend down | `tests/integration/test_sip_trunk_failover.py` | ✅ |
| 12 | CDR accuracy + billing alignment | `tests/integration/test_cdr_billing.py` | ✅ |

## CI Integration

GitHub Actions workflows in `.github/workflows/`:

| Workflow | Trigger | Tests Run |
|----------|---------|-----------|
| `ci.yml` | Push/PR to `master` | Docker build, compose config validation, OpenSIPS syntax check |
| `deploy.yml` | Manual / tag | VPS deployment via Ansible |
| `squad-heartbeat.yml` | Schedule (hourly) | Agent orchestrator health check |
| `squad-triage.yml` | Issue/PR open | Auto-label, assign, prioritize |
| `sync-squad-labels.yml` | Schedule (daily) | Label consistency across squad repos |

**Local validation commands (from AGENTS.md):**
\`\`\`bash
# OpenSIPS config syntax
docker run --rm -e DB_HOST=postgres ... tsisip-opensips:latest /entrypoint.sh opensips -c -f /etc/opensips/opensips.cfg

# Runtime SIP validation (OPTIONS 200 OK)
docker run --rm --network tsisip_sip_edge alpine sh -c "apk add sipsak && sipsak -s sip:opensips:5060 -vv"

# Runtime SIP validation (INVITE 407)
python3 -c "import socket; ... send INVITE ... expect 407"
\`\`\`

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1.0 | 2026-05-26 | DocGuard Generate | Auto-generated skeleton (49 test files, 0/0 mapped) |
| 0.2.0 | 2026-05-26 | Kimi AI | Mapped real test files; added test categories, coverage targets, CI workflows, critical journeys, local validation commands |

---

## Standards Reference

> **Aligned with**: ISO/IEC/IEEE 29119-3:2022 — Test Documentation
>
> **Sections covered**: Test Categories, Coverage Rules, Test Matrix, Tool Configuration, CI Integration, Critical User Journeys
>
> **Reference**: ISO/IEC/IEEE, "Software and systems engineering — Software testing — Part 3: Test documentation." International Standard, 2022
>
> *Standards alignment inspired by RAG-grounded generation (Lopez et al., AITPG, IEEE TSE 2026).*
