-- TSiSIP Custom PostgreSQL Extensions
-- Extends stock OpenSIPS 3.6 schema with tenant, routing, and audit metadata.

CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- tenants: multi-tenant SIP domain registry
CREATE TABLE IF NOT EXISTS tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(128) NOT NULL,
    sip_domain VARCHAR(255) NOT NULL UNIQUE,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Extend subscriber with tenant context and routing group
ALTER TABLE subscriber
    ADD COLUMN IF NOT EXISTS tenant_id UUID NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
    ADD COLUMN IF NOT EXISTS routing_group INTEGER NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS enabled BOOLEAN NOT NULL DEFAULT TRUE;

-- Add foreign key constraint after columns exist (idempotent for re-runs)
-- Note: First migration must ensure a default tenant exists before applying FK.
-- For initialization safety, we skip the strict FK here and enforce it in application layer.

CREATE UNIQUE INDEX IF NOT EXISTS uq_subscriber_tenant_username_domain
    ON subscriber(tenant_id, username, domain);
CREATE INDEX IF NOT EXISTS idx_subscriber_tenant_domain
    ON subscriber(tenant_id, domain);

-- header_routing_rules: tenant-scoped header-based routing metadata
CREATE TABLE IF NOT EXISTS header_routing_rules (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    header_name VARCHAR(64) NOT NULL,
    match_value VARCHAR(255) NOT NULL,
    match_type VARCHAR(16) NOT NULL DEFAULT 'exact'
        CHECK (match_type IN ('exact')),
    dispatcher_setid INTEGER NOT NULL,
    priority INTEGER NOT NULL DEFAULT 100,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (tenant_id, header_name, match_value)
);

CREATE INDEX IF NOT EXISTS idx_header_routing_lookup
    ON header_routing_rules(tenant_id, enabled, header_name, match_value, priority)
    WHERE enabled = true;

-- pbx_backends: tenant-owned PBX metadata linking to dispatcher sets
CREATE TABLE IF NOT EXISTS pbx_backends (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL,
    dispatcher_setid INTEGER NOT NULL CHECK (dispatcher_setid > 0),
    label VARCHAR(128) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (tenant_id, label)
);

CREATE INDEX IF NOT EXISTS idx_pbx_backends_dispatcher_setid
    ON pbx_backends(tenant_id, dispatcher_setid);

-- auth_audit_log: edge authentication event log
CREATE TABLE IF NOT EXISTS auth_audit_log (
    id BIGSERIAL PRIMARY KEY,
    event_time TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    username VARCHAR(64) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    source_ip INET NOT NULL,
    sip_method VARCHAR(16) NOT NULL,
    result VARCHAR(32) NOT NULL,
    call_id VARCHAR(255)
);

CREATE INDEX IF NOT EXISTS idx_auth_audit_event_time
    ON auth_audit_log(event_time);
CREATE INDEX IF NOT EXISTS idx_auth_audit_user_domain
    ON auth_audit_log(username, domain);
CREATE INDEX IF NOT EXISTS idx_auth_audit_source_ip
    ON auth_audit_log(source_ip);

-- userblacklist: per-user and global ban/allow lists for OpenSIPS userblacklist module
CREATE TABLE IF NOT EXISTS userblacklist (
    id SERIAL PRIMARY KEY,
    username VARCHAR(64) NOT NULL DEFAULT '',
    domain VARCHAR(255) NOT NULL DEFAULT '',
    prefix VARCHAR(64) NOT NULL DEFAULT '',
    whitelist SMALLINT NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_userblacklist_lookup
    ON userblacklist(username, domain, prefix);

INSERT INTO version (table_name, table_version) VALUES
    ('userblacklist', 2)
ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;

-- cdr: Call Detail Records for billing and analytics
CREATE TABLE IF NOT EXISTS cdr (
    id BIGSERIAL PRIMARY KEY,
    call_id VARCHAR(255) NOT NULL,
    call_start TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    call_end TIMESTAMPTZ,
    duration INTEGER DEFAULT 0,
    from_user VARCHAR(64) NOT NULL,
    from_domain VARCHAR(255) NOT NULL,
    to_user VARCHAR(64) NOT NULL,
    to_domain VARCHAR(255) NOT NULL,
    source_ip INET NOT NULL,
    destination_ip INET,
    sip_method VARCHAR(16) NOT NULL,
    call_status VARCHAR(32) NOT NULL DEFAULT 'unknown',
    setup_time_ms INTEGER,
    dialog_id VARCHAR(64),
    tenant_id UUID,
    dispatcher_setid INTEGER,
    backend_label VARCHAR(128),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cdr_call_id ON cdr(call_id);
CREATE INDEX IF NOT EXISTS idx_cdr_call_start ON cdr(call_start);
CREATE INDEX IF NOT EXISTS idx_cdr_from_user ON cdr(from_user);
CREATE INDEX IF NOT EXISTS idx_cdr_tenant ON cdr(tenant_id);
CREATE INDEX IF NOT EXISTS idx_cdr_status ON cdr(call_status);

-- trunk_ips: trusted SIP trunk endpoints requiring mutual TLS (T2.3)
CREATE TABLE IF NOT EXISTS trunk_ips (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ip_address INET NOT NULL,
    label VARCHAR(128) NOT NULL,
    tenant_id UUID,
    require_mtls BOOLEAN NOT NULL DEFAULT TRUE,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (ip_address, label)
);

CREATE INDEX IF NOT EXISTS idx_trunk_ips_lookup
    ON trunk_ips(ip_address, enabled)
    WHERE enabled = true;
