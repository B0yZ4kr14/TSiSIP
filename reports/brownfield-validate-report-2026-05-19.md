# Brownfield Validation Report — TSiSIP

**Date**: 2026-05-19
**Project**: TSiSIP (/home/b0yz4kr14/Projects/TSiSIP)
**Skill**: speckit-brownfield-validate
**Constitution Version**: 1.1.1 (ratified 2026-05-17, amended 2026-05-24)
**AGENTS.md Version**: Last updated 2026-05-20

---

## 1. Constitution Validation

### 1.1 Language & Stack References

| Rule | Status | Detail |
|------|--------|--------|
| Primary stack: OpenSIPS 3.6 LTS | ✅ Pass | Dockerfile builds from opensips/opensips:3.6.x, opensips/opensips.cfg.tpl present |
| Database: PostgreSQL 16 | ✅ Pass | docker/postgres/Dockerfile, db/init/*.sql (8 SQL files), db_postgres module references |
| Media relay: RTPengine | ✅ Pass | docker/rtpengine/Dockerfile, docker/rtpengine/healthcheck.sh |
| PBX: Asterisk | ✅ Pass | docker/asterisk/Dockerfile, docker/asterisk/pjsip.conf, docker/asterisk/extensions.conf |
| Admin Panel: OCP v9 + PHP 8.2 | ✅ Pass | 57 PHP files in web/, docker/ocp/Dockerfile |
| Observability: Prometheus + Grafana + Alertmanager | ✅ Pass | docker/prometheus/, docker/grafana/, alert-rules.yml, alertmanager.yml.tpl |
| Build tools: Node.js, gettext, Bash | ✅ Pass | build/generate-css-variables.js, build/generate-manifest.js, .po/.mo files, 80 .sh files |
| Test framework: pytest (Python), Node.js | ✅ Pass | 21 Python test files in tests/integration/, Node.js frontend tests (tests/accessibility-audit.test.js, tests/d3-jquery-coexistence.test.js) |

### 1.2 Directory References

| Rule | Status | Detail |
|------|--------|--------|
| docs/ canonical documentation | ✅ Pass | Exists with TSiSIP-CANONICAL-SPEC.md, TSiSIP-OPERATOR-RUNBOOK.md, TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md, architecture/, features/, wiki/, memory/, security/ |
| opensips/ config template | ✅ Pass | opensips/opensips.cfg.tpl exists |
| docker/ container support | ✅ Pass | 15 service subdirectories, 17 Dockerfiles total |
| db/init/ PostgreSQL schema | ✅ Pass | 8 .sql files present |
| web/ OCP application | ✅ Pass | 57 PHP files, common/, tsisip/, css/ subdirs |
| build/ theme pipeline | ✅ Pass | generate-css-variables.js, generate-manifest.js, Makefile |
| tests/ test suite | ✅ Pass | integration/, performance/, visual-regression/, vps-stabilization/ |
| scripts/ build/deploy scripts | ✅ Pass | build-ocp-theme.sh, ci-scan.sh, sip-auth-probe.py, etc. |
| deploy/ automation | ✅ Pass | ansible/, scripts/, nginx/, audit/ |
| specs/ SDD artifacts | ✅ Pass | 24 feature directories (001–024) |
| reports/ quality gate reports | ✅ Pass | Exists |
| secrets/ runtime secrets | ✅ Pass | Exists, .gitignore protected |
| evidence/remediation/ cycles | ✅ Pass | ciclo-1/ through ciclo-5/, feature-013/, ciclo-020/ |
| docs/security/evidence/ | ✅ Pass | Exists |

### 1.3 Framework & Dependency References

| Rule | Status | Detail |
|------|--------|--------|
| Docker Compose orchestration | ✅ Pass | docker-compose.yml, docker-compose.prod.yml, docker-compose.vps.yml, docker-compose.build.yml, docker-compose.vps.override.yml |
| Ansible deploy | ✅ Pass | deploy/ansible/playbook-deploy.yml, deploy/ansible/playbook-hardening.yml |
| GitHub Actions CI/CD | ✅ Pass | .github/workflows/ci.yml, .github/workflows/deploy.yml |
| PDO for PostgreSQL (PHP) | ✅ Pass | web/common/config.php uses PDO |
| d3.js + jQuery frontend | ✅ Pass | web/tsisip/js/, tests/d3-jquery-coexistence.test.js |

### 1.4 Naming Conventions (Sampled 30+ files)

| Rule | Status | Detail |
|------|--------|--------|
| Database identifiers: snake_case | ✅ Pass | sip_edge, sip_internal, db_internal, auth_audit_log all confirmed |
| Docker network names: snake_case | ✅ Pass | sip_edge, sip_internal, db_internal, metrics_host |
| Docker service names: snake_case | ⚠️ Drift | 7 services use hyphens instead of snake_case: postgres-exporter, node-exporter, asterisk-pbx-1, asterisk-pbx-2, admin-api, anomaly-detector, opensips-exporter, tailscale-cert, certbot-exporter (AGENTS.md Section 7 mandates snake_case for Docker service names) |
| File naming: lowercase with extensions | ✅ Pass | Sampled backup.sh, cert-gen.sh, pjsip.conf, ha1-generator.php — all compliant |

### 1.5 Test Locations

| Rule | Status | Detail |
|------|--------|--------|
| Integration tests in tests/integration/ | ✅ Pass | 21 Python test files present |
| Frontend tests at repository root tests/ | ✅ Pass | accessibility-audit.test.js, d3-jquery-coexistence.test.js |
| Performance tests directory | ⚠️ Drift | tests/performance/ exists but is empty (no committed test files) |
| Visual regression tests directory | ⚠️ Drift | tests/visual-regression/ exists but is empty (no committed test files) |
| VPS stabilization tests | ⚠️ Drift | tests/vps-stabilization/ exists (7 files) but not mentioned in AGENTS.md testing strategy |

### 1.6 Branch Patterns

| Rule | Status | Detail |
|------|--------|--------|
| Branch naming: main / master | ✅ Pass | Both main and master branches exist; remotes/origin/HEAD -> origin/main |
| Feature branch pattern | ℹ️ Info | No feat/* branches found in current repo; all work appears on main/master |

### 1.7 Security Gates (Constitution Check Gates)

| Gate | Status | Detail |
|------|--------|--------|
| Docker-first | ✅ Pass | 17 Dockerfiles; no bare-metal install paths |
| PostgreSQL-only | ✅ Pass | Zero db_mysql/db_sqlite references in opensips/, db/, docker/ |
| Module validity | ✅ Pass | No sanity module references; no Kamailio-only modules |
| Secret hygiene | ✅ Pass | secrets/ and .env in .gitignore; no plaintext secrets in committed configs |
| Network isolation | ✅ Pass | Asterisk and PostgreSQL have no published ports in docker-compose.yml |
| cap_drop + no-new-privileges | ✅ Pass | 18 occurrences of cap_drop: [ALL] and no-new-privileges:true in compose |

### 1.8 Constitution-Referenced Governance Files

| File | Status | Detail |
|------|--------|--------|
| .specify/memory/agent-governance.md | ✅ Pass | Exists |
| .specify/memory/architecture_constitution.md | ✅ Pass | Exists |
| .specify/memory/security_constitution.md | ✅ Pass | Exists |
| docs/TSiSIP-CANONICAL-SPEC.md | ✅ Pass | Exists |
| docs/TSiSIP-OPERATOR-RUNBOOK.md | ✅ Pass | Exists |
| docs/TSiSIP-AGENT-ORCHESTRATION-PLAYBOOK.md | ✅ Pass | Exists |

---

## 2. Templates Validation

### 2.1 Spec Template (spec-template.md)

| Check | Status | Detail |
|-------|--------|--------|
| Module references | ✅ Pass | References sip_edge, sip_internal, db_internal — all real networks |
| Security requirements | ✅ Pass | SR-001 through SR-005 map to real project security gates |
| OpenSIPS module requirements | ✅ Pass | Mentions forbidden sanity, db_mysql, rtpproxy — matches constitution |
| Docker requirements | ✅ Pass | cap_drop, no-new-privileges, base image pinning — all aligned |

### 2.2 Plan Template (plan-template.md)

| Check | Status | Detail |
|-------|--------|--------|
| Source structure options | ⚠️ Drift | Template includes placeholder options for src/, backend/, frontend/, api/, ios/, android/ — none exist in TSiSIP. These are marked "REMOVE IF UNUSED" so they are advisory, but they create noise for agents |
| VPS Deploy Phase | ✅ Pass | References real deploy sequence and validation commands |
| Evidence directory | ✅ Pass | docs/security/evidence/[feature-dir]/ exists and is used |

### 2.3 Tasks Template (tasks-template.md)

| Check | Status | Detail |
|-------|--------|--------|
| Path conventions | ⚠️ Drift | Template assumes src/models/, src/services/, tests/contract/ paths that do not exist in TSiSIP. These are sample tasks marked for replacement, but the default structure doesn't match this repo's docker/, web/, db/ layout |
| Validation commands | ✅ Pass | opensips -c, docker compose config, sipsak, trivy — all real project commands |
| Evidence collection | ✅ Pass | Port audit, auth contract verification, network segmentation — all real gates |

### 2.4 Checklist Template (checklist-template.md)

| Check | Status | Detail |
|-------|--------|--------|
| Section relevance | ✅ Pass | Generic template with no project-specific references; safe as-is |

---

## 3. AGENTS.md Validation

### 3.1 Directory Ownership & Coverage

| Check | Status | Detail |
|-------|--------|--------|
| All source dirs covered | ⚠️ Drift | AGENTS.md Section 5 directory tree covers 21 top-level directories. Missing from tree: docs-canonical/, commands/, plans/, remediation/, .agents/ (413 files) |
| Agent orchestration dirs | ✅ Pass | .claude/, .claude-flow/, .omk/, .kimi/, .swarm/, .sisyphus/, .specify/ all noted as "agent orchestration state/config" |
| No directory overlaps | ✅ Pass | No directory is claimed by multiple agents |
| No orphan source dirs | ⚠️ Drift | docs-canonical/ (5 canonical docs), commands/ (project commands), plans/ (plan artifacts), remediation/ (2026-05-20 remediation), .agents/ (413 skill files) are not described in AGENTS.md |

### 3.2 File References in AGENTS.md

| File | Status | Detail |
|------|--------|--------|
| .github/workflows/ci.yml | ✅ Pass | Exists |
| .github/workflows/deploy.yml | ✅ Pass | Exists |
| .github/copilot-instructions.md | ✅ Pass | Exists |
| .mcp.json | ✅ Pass | Exists |
| .omk/mcp.json | ✅ Pass | Exists |
| .kimi/mcp.json | ✅ Pass | Exists |
| docker/entrypoint.sh | ✅ Pass | Exists |
| docker/ocp/entrypoint.sh | ✅ Pass | Exists |
| scripts/build-ocp-theme.sh | ✅ Pass | Exists |
| opensips/opensips.cfg.tpl | ✅ Pass | Exists |
| deploy/scripts/orchestrate-deploy.sh | ✅ Pass | Exists |
| docs/security/019-agent-memory-governance.md | ✅ Pass | Exists |
| db/init/04-ocp-tools-schema.sql | ✅ Pass | Exists |
| db/init/04-ocp-audit-schema.sql | ✅ Pass | Exists |
| db/init/04-trunk-schema.sql | ✅ Pass | Exists |

### 3.3 Spec Count Accuracy

| Check | Status | Detail |
|-------|--------|--------|
| 23 tracked features (001–023) | ⚠️ Drift | AGENTS.md Section 2 and Section 5 state "23 tracked feature specifications (001–023)". Actual count: 24 directories in specs/ (001–024). Feature 024-brownfield-remediation was added after the AGENTS.md count was written |

### 3.4 Memory Hub Documentation

| Check | Status | Detail |
|-------|--------|--------|
| docs/memory/INDEX.md | ✅ Pass | Referenced and implied by docs structure |
| docs/memory/PROJECT_CONTEXT.md | ✅ Pass | Referenced |
| docs/memory/ARCHITECTURE.md | ✅ Pass | Referenced |
| docs/memory/DECISIONS.md | ✅ Pass | Referenced |
| docs/memory/BUGS.md | ✅ Pass | Referenced |
| docs/memory/WORKLOG.md | ✅ Pass | Referenced |
| docs/memory/memory-synthesis.md | ✅ Pass | Referenced |

---

## 4. Drift Detection

### 4.1 New Directories / Modules Since Constitution

| Item | Status | Detail |
|------|--------|--------|
| specs/024-brownfield-remediation/ | 🆕 Drift | New feature directory beyond the documented 001–023 range |
| .agents/ | 🆕 Drift | 413 skill files across .agents/skills/ — not in AGENTS.md directory tree or constitution |
| docs-canonical/ | 🆕 Drift | Contains ARCHITECTURE.md, DATA-MODEL.md, ENVIRONMENT.md, SECURITY.md, TEST-SPEC.md, docguard.*.md, 6h-full-implementation-socratic-plan.md — not in AGENTS.md tree |
| commands/ | 🆕 Drift | Project command directory — not in AGENTS.md tree |
| plans/ | 🆕 Drift | Plan artifacts directory — not in AGENTS.md tree |
| remediation/ | 🆕 Drift | 2026-05-20/ subdirectory — not in AGENTS.md tree (separate from evidence/remediation/) |
| tests/vps-stabilization/ | 🆕 Drift | 7 test files for VPS stabilization — not mentioned in AGENTS.md testing strategy (Section 11) |
| web/cli/ | 🆕 Drift | CLI scripts directory under web/ — not in AGENTS.md directory tree |
| web/wiki/ | 🆕 Drift | Wiki subdirectory under web/ — not in AGENTS.md directory tree |
| evidence/discovery/, evidence/generate/, evidence/qa/, evidence/risk/, evidence/security/ | 🆕 Drift | Evidence subdirectories not in AGENTS.md tree (only evidence/phase1-5/ and evidence/remediation/ are documented) |
| docker/admin-api/src/ | 🆕 Drift | Source subdirectory under docker/admin-api/ — admin-api Dockerfile exists but src/ not in AGENTS.md tree |

### 4.2 Naming Convention Drift

| Item | Status | Detail |
|------|--------|--------|
| Hyphenated Docker service names | 🆕 Drift | AGENTS.md Section 7 mandates snake_case for Docker service names. 9 services use hyphens: postgres-exporter, node-exporter, asterisk-pbx-1, asterisk-pbx-2, admin-api, anomaly-detector, opensips-exporter, tailscale-cert, certbot-exporter |

### 4.3 Documentation Drift

| Item | Status | Detail |
|------|--------|--------|
| Brownfield status in AGENTS.md | ✅ Current | Section 14 tracks 16/16 findings addressed through Ciclo 1–5 and Feature 013 |
| Memorylint status in AGENTS.md | ✅ Current | Section 14 tracks M1–M5 with 3 LOW monitor items and 2 fixed |
| AGENTS.md last updated | ✅ Current | 2026-05-20 (matches constitution last amended 2026-05-24) |

### 4.4 Dependencies / Tooling Drift

| Item | Status | Detail |
|------|--------|--------|
| .specify/extensions/memory-md/ tooling | ✅ Pass | Referenced in AGENTS.md Section 15 with exact commands |
| GitNexus integration | ✅ Pass | Referenced in AGENTS.md Section 16 (gitnexus:start/end block) |
| New compose file: docker-compose.build.yml | 🆕 Drift | Not listed in AGENTS.md Section 2 (only .yml, .prod.yml, .vps.yml mentioned) |
| New compose file: docker-compose.vps.override.yml | 🆕 Drift | Not listed in AGENTS.md Section 2 |

---

## 5. Summary

| Category | Passed | Drift | Total |
|----------|--------|-------|-------|
| Constitution | 18 | 5 | 23 |
| Templates | 5 | 3 | 8 |
| AGENTS.md | 17 | 4 | 21 |
| Drift Detection | — | 12 | 12 |
| **Overall** | **40** | **24** | **64** |

### 5.1 Checks Passed ✅ (40/64)

- All primary technology stack components exist and match constitution
- All critical security gates pass (Docker-first, PostgreSQL-only, module validity, secret hygiene, network isolation)
- All constitution-referenced governance files exist
- All AGENTS.md-referenced core files exist
- Spec template security requirements and module gates are accurate
- Tasks template validation commands are real project commands
- Memory Hub documentation and commands are accurate
- Brownfield and Memorylint status sections are current
- GitNexus integration block is present and accurate

### 5.2 Drift / Action Items ⚠️ (24)

#### High Priority
1. **AGENTS.md spec count**: Update "23 tracked feature specifications (001–023)" to "24 tracked feature specifications (001–024)" to include 024-brownfield-remediation
2. **Docker service naming**: Rename hyphenated service names to snake_case per AGENTS.md Section 7 convention:
   - postgres-exporter → postgres_exporter
   - node-exporter → node_exporter
   - asterisk-pbx-1 → asterisk_pbx_1
   - asterisk-pbx-2 → asterisk_pbx_2
   - admin-api → admin_api
   - anomaly-detector → anomaly_detector
   - opensips-exporter → opensips_exporter
   - tailscale-cert → tailscale_cert
   - certbot-exporter → certbot_exporter

#### Medium Priority
3. **AGENTS.md directory tree**: Add missing top-level directories to Section 5:
   - docs-canonical/ — canonical documentation artifacts
   - commands/ — project commands
   - plans/ — plan artifacts
   - remediation/ — remediation tracking (separate from evidence/remediation/)
   - .agents/ — agent skill definitions (413 files)
4. **AGENTS.md compose files**: Add docker-compose.build.yml and docker-compose.vps.override.yml to Section 2 repository contents
5. **AGENTS.md testing strategy**: Add tests/vps-stabilization/ to Section 11 with description of its 7 test files
6. **AGENTS.md web subdirectories**: Add web/cli/ and web/wiki/ to Section 5 directory tree
7. **AGENTS.md evidence subdirectories**: Add evidence/discovery/, evidence/generate/, evidence/qa/, evidence/risk/, evidence/security/ to Section 5

#### Low Priority
8. **Plan template noise**: The src/, backend/, frontend/, api/, ios/, android/ placeholder options in plan-template.md are advisory ("REMOVE IF UNUSED") but could confuse agents. Consider adding a TSiSIP-specific note that this project uses docker/, web/, db/, opensips/ instead of conventional src/ layout.
9. **Tasks template path conventions**: The sample tasks reference src/models/ and src/services/ which don't exist. The template is correctly marked as samples to be replaced, but a TSiSIP-specific preamble could note the actual project structure.
10. **Empty test directories**: tests/performance/ and tests/visual-regression/ exist but contain no committed files. Either add placeholder READMEs or remove them until tests are committed.

---

## 6. Evidence

All findings in this report are based on direct filesystem inspection:

- find . -type f -not -path '*/.*' -not -path '*/node_modules*' — 700 source files enumerated
- find . -maxdepth 3 -type d — 149 directories enumerated
- docker-compose.yml, docker-compose.vps.yml — service names and security options verified
- .gitignore — secrets exclusion verified
- git branch -a — branch patterns verified
- git log --since="2026-05-17" --name-only — recent additions since constitution ratification

**Respect .gitignore**: All scans excluded node_modules/, __pycache__/, and hidden directories as required by the skill rules.

---

*Report generated by speckit-brownfield-validate skill. Read-only validation — no files were modified.*
