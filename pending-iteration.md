---
status: pending
created: 2026-05-24
change_request: "Project-wide documentation alignment iteration: synchronize AGENTS.md, STATUS.md, .env.example, and all spec cross-references with actual implementation state after VPS go-live (Feature 022). Apply socratic-popperian falsification to all claims."
scope: Feature-wide
---

## Change Summary

Synchronize all canonical documentation with actual implementation state post-VPS go-live. Eliminate falsifiable false claims. Update artifact inventories, service counts, file lists, and schema references to reflect 23 specs (001-023), 10 vps-lite services, 7 db/init files, 29 OCP PHP files, and userland-proxy=false reality.

## Implementation Progress

- **Tasks completed**: Feature 022 vps-go-live-stabilization is functionally complete (all containers healthy on VPS)
- **Current phase**: Post-go-live documentation consolidation
- **Files changed on branch**: 21 files in previous commit (279ae33) addressing userland-proxy impact
- **Adhoc changes**: Nginx proxy now uses OCP Docker bridge IP instead of 127.0.0.1:8084

## Impact Assessment

| Artifact | Action | Details |
|----------|--------|---------|
| AGENTS.md | Major rewrite | §2 (repo state), §3 (stack), §4 (network model), §5 (directory structure, service counts, file inventories), §6 (build commands), §10 (rejected patterns), §12 (quick refs) |
| STATUS.md | Major rewrite | Update feature list 001-023, service counts, port mapping, remove stale claims |
| .env.example | Fix | Remove duplicate keys (HOST_PUBLIC_IP, ACME_EMAIL, TLS_DOMAIN) |
| docker-compose.vps.yml | Comment | Already updated in 279ae33; verify consistency |
| specs/022 | Update | AC4 already updated; ensure blueprint and security-constraints are aligned |
| docs/TSiSIP-OPERATOR-RUNBOOK.md | Update | Service table, OCP access method |
| docs/wiki/system-overview.md | Update | Exposure table |

## Risk Checks

- [x] No completed tasks invalidated — this is documentation-only
- [x] No scope boundary violations — alignment with existing implementation
- [x] No downstream dependency breaks — no code changes

## Planned Changes

### AGENTS.md
- Update spec count: 11 → 23 (001-023)
- Update repo state: reflect committed state (docker-compose.vps.yml exists, no commits yet → commits exist)
- Update technology stack: add admin-api, certbot, certbot-exporter, backup services
- Update Docker network model: metrics_host is internal: true; correct service counts per network
- Update directory structure: add docker/admin-api/, docker/certbot/, docker/certbot-exporter/, docker/tailscale-cert/
- Update db/init files: list all 7 files (01-05 including 04-ocp-tools-schema, 04-ocp-audit-schema, 04-trunk-schema, 05-seed-trunk-data)
- Update web/ file list: enumerate all 29 PHP files (add audit-log, audit-export, trunk-providers, trunk-dids, trunk-status, tenants, users, dialog, dialplan, domains, header-routing, tls-management, mi-commands, statistics, userblacklist)
- Update docker-compose service counts: dev=16, vps=10, prod=16
- Add userland-proxy=false caveat to OCP access section
- Update build commands: add certbot-exporter, backup builds; update validation commands
- Update spec count in §12 Quick References

### STATUS.md
- Update date
- Add features 010-023 to status table
- Correct service counts and port mappings
- Mark Feature 022 as complete
- Add notes about userland-proxy=false and nginx proxy

### .env.example
- Remove duplicate HOST_PUBLIC_IP (lines 12 and 72)
- Remove duplicate ACME_EMAIL (lines 39 and 78)
- Remove duplicate TLS_DOMAIN (lines 38 and 79)

### docs/TSiSIP-OPERATOR-RUNBOOK.md
- Update service table: correct networks, ports, notes
- Add admin-api service
- Update OCP access notes with userland-proxy context

### docs/wiki/system-overview.md
- Update exposure table for OCP

### post-iteration-validation.md (new)
- Create comprehensive validation checklist
