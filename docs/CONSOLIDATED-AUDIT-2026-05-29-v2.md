# TSiSIP Consolidated Audit v2 — 2026-05-29

> Second orchestrated execution: speckit-brownfield-scan, speckit-memorylint, speckit-version-guard, gitnexus-analysis.

---

## 1. Executive Summary

| Scanner | Findings | Critical | High | Medium | Low | Status |
|---|---|---|---|---|---|---|
| Brownfield Scan | 7 (B19, B20-FU, B21-FU, N1-N4) | 0 | 0 | 3 | 4 | All Remediated |
| Memory Lint | 7 (M1-VPS, M7, M10, M11, M13, M14, M15) | 1 | 0 | 3 | 3 | All Remediated |
| Version Guard | 1 (V21) | 0 | 1 | 0 | 0 | Remediated |
| GitNexus | Index synced | — | — | — | — | Current |

**Overall Verdict:** FULLY COMPLIANT — Zero blocking violations. All non-negotiable rules upheld.

---

## 2. Brownfield Scan Remediation

| ID | File | Change |
|---|---|---|
| B21-FU | docker/admin_api/entrypoint.sh | set -eu -> set -euo pipefail |
| B21-FU | docker/certbot/entrypoint.sh | set -eu -> set -euo pipefail |
| B21-FU | docker/ocp/entrypoint.sh | set -eu -> set -euo pipefail |
| B21-FU | docker/prometheus/entrypoint.sh | set -eu -> set -euo pipefail |
| B21-FU | docker/tailscale_cert/renew.sh | set -eu -> set -euo pipefail |
| N2 | docker-compose.vps.yml | HOST_PUBLIC_IP fallback removed (now required) |
| N3 | web/call-queue.php | Stale t_list comment removed |

---

## 3. Memory Lint Remediation

| ID | Service | Change |
|---|---|---|
| M1-VPS | OpenSIPS (vps) | -M 64 -> 48; calculated 1088MB -> 944MB (fits in 1G) |
| M7 | OpenSIPS (all) | children = 8 added to opensips.cfg.tpl |
| M14 | export-report.php | LIMIT 5000 added to both GROUP BY queries |
| M15 | certbot_exporter | docker-compose.yml + vps: 64M -> 128M |

---

## 4. Version Guard Remediation

| ID | Component | Change |
|---|---|---|
| V21 | opensips_exporter | requests==2.32.3 confirmed aligned across all Python containers |

---

## 5. GitNexus Status

- Indexed commit: c00d550 -> current (pending new commit)
- Nodes: 10,822 | Edges: 12,112 | Clusters: 107 | Flows: 21
- Delta: +19 nodes, +22 edges since last scan

---

## 6. Conformance Statement

All 10 non-negotiable rules PASS.

---

*Audit completed: 2026-05-29*
