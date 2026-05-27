# Decisions

Record durable architecture, release, runtime, and safety decisions.

For each decision, include:

- date and short title
- affected files or surfaces
- evidence commands or artifacts
- rollback or revisit trigger

Never store secrets, raw MCP env, tokens, or private user data.

## 2026-05-19 — Canonical TSiAPP SSH Access

**Decision**: Standardize all TSiAPP operator access through a single SSH key pair (`TSiHomeLab`, Ed25519) with four connection aliases (public IP + Tailscale × root + tsi).

**Affected files**:
- `deploy/ssh/TSiAPP-config`
- `docs/VPS-TSiAPP-ACCESS.md`
- `.ssh/config.template`
- `deploy/ansible/inventory.yml`
- `deploy/scripts/orchestrate-deploy.sh`
- `.env.example`

**Evidence**:
- SSH config validates with `ssh -G tsia-tsi`
- Ansible ping succeeds after key distribution
- Deploy script defaults to public IP (179.190.15.116) and TSiHomeLab key

**Rollback trigger**: Key compromise, host migration, or Tailscale policy change.

**Security notes**:
- Password authentication disabled after bootstrap
- Bootstrap passwords live in operator vault only
- `.gitignore` excludes `.ssh/config` and `known_hosts`

## 2026-05-26 — Schema Drift: tenant_id as VARCHAR(36) vs UUID

**Decision:** Accept the `VARCHAR(36)` deviation for `subscriber.tenant_id`, `header_routing_rules.tenant_id`, and `pbx_backends.tenant_id` as a pragmatic bootstrap-ordering compromise, with documented migration path to UUID.

**Context:** The stock OpenSIPS schema (`01-stock-opensips-schema.sql`) runs before the `tenants` table is created. A strict `UUID` foreign key would fail during `db/init` bootstrap because the referenced table does not yet exist.

**Affected files:**
- `db/init/02-tsisip-extensions.sql`
- `db/init/03-seed-data.sql`

**Evidence:**
- Brownfield scan B19 confirms drift in 3 tables
- Canonical spec Section 12 defines `tenants.id` as `UUID PRIMARY KEY DEFAULT gen_random_uuid()`
- `trunk_ips.tenant_id` and `sip_trunk_did_mappings.tenant_id` correctly use `UUID`

**Migration path (future):**
1. Create `tenants` table in a pre-bootstrap script (before stock schema)
2. Alter `subscriber`, `header_routing_rules`, `pbx_backends` to use `UUID`
3. Update seed data to use valid UUID references

### 2026-05-27 — Frontend Orphan Consolidation Decision

**Decision**: Consolidate all orphan pages into role-nav.php navigation rather than maintaining a separate wiki index.

**Context**: Frontend audit identified 18 pages not linked from any menu. Users could only access them via direct URL or wiki index.

**Affected files**:
- `web/common/role-nav.php` (added 18 entries across System, Admin, Account sections)
- `web/common/header.php` (added wiki button)
- `web/health.php` (deleted — redundant with system-health)
- `web/healthcheck-audit.php` (deleted — stub)
- `web/ocp/trunk-*.php` (deleted — duplicates of root-level pages)

**Evidence**:
- 17/17 OCP smoke tests PASS post-consolidation
- Zero orphan pages verified by filesystem scan
- Zero broken links verified by curl loop

**Rollback trigger**: Navigation UX feedback requiring section reorganization.

### 2026-05-27 — OpenSIPS `children` Directive Removal

**Decision**: Remove `children = 8` from `opensips.cfg.tpl` because OpenSIPS 3.6.6 rejects it as an unknown config variable.

**Context**: OpenSIPS 3.6.6 changed config parsing. The `children` parameter in the main route section causes `parse error: unknown config variable`.

**Affected files**:
- `opensips/opensips.cfg.tpl`

**Evidence**:
- Config validation passes after removal: `opensips -c -f /etc/opensips/opensips.cfg`
- Runtime children controlled via Docker Compose `deploy.resources.limits` and process scaling

**Rollback trigger**: Migration to OpenSIPS 3.7+ where `children` syntax may be restored.

### 2026-05-27 — Audit Finding Remediation Batch

**Decision**: Resolve all feasible findings from consolidated audit 2026-05-26; defer operator-dependent items.

**Findings resolved**:
- M1/M2: OpenSIPS and PostgreSQL memory tuning
- D9/F1/A2: Version alignment and pinning
- B17/B18/B21-B28: Documentation, entrypoints, EXPOSE, seed data
- M5/M6: Bounded queries with LIMIT
- M3/M4/M8/M9/M11: Memory, pooler, alerting

**Findings deferred**:
- M10: Host swap configuration (requires root access to VPS)
- B20: Legacy parity schema columns (low risk, future sprint)
- External: DNS, firewall ACL, S3 credentials

**Evidence**:
- `reports/AUDITORIA-FRONTEND-TSiSIP-2026-05-27.md`
- `reports/AUDIT-FOLLOWUP-2026-05-27.md`
- Commit `07582b9`

**Rollback trigger**: Audit regression requiring revert of memory parameters.

**Rollback trigger:** Canonical spec version bump requiring strict UUID compliance, or LGPD audit finding.
