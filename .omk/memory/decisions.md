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

**Rollback trigger:** Canonical spec version bump requiring strict UUID compliance, or LGPD audit finding.
