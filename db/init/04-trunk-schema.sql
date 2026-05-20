-- TSiSIP SIP Trunk Provider Schema
-- Feature 014-C: SIP Trunk Provider Integration — Wave 1 (Database Schema)
-- Extends the TSiSIP schema with trunk provider configuration, DID mappings,
-- and UAC registrant state. All credential columns use pgcrypto encryption.

-- Ensure pgcrypto is available for credential encryption
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- sip_trunk_providers: external carrier SIP trunk configuration
CREATE TABLE IF NOT EXISTS sip_trunk_providers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    host VARCHAR(255) NOT NULL,
    port INTEGER NOT NULL DEFAULT 5060 CHECK (port > 0 AND port <= 65535),
    transport VARCHAR(10) NOT NULL DEFAULT 'udp'
        CHECK (transport IN ('udp', 'tcp', 'tls', 'ws', 'wss')),
    auth_username VARCHAR(255),
    auth_password_encrypted BYTEA,
    from_domain VARCHAR(255),
    caller_id_prefix VARCHAR(50),
    priority INTEGER NOT NULL DEFAULT 100,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    registration_required BOOLEAN NOT NULL DEFAULT FALSE,
    registration_expiry INTEGER NOT NULL DEFAULT 3600 CHECK (registration_expiry > 0),
    max_cps INTEGER NOT NULL DEFAULT 10 CHECK (max_cps > 0),
    max_concurrent INTEGER NOT NULL DEFAULT 100 CHECK (max_concurrent > 0),
    require_mtls BOOLEAN NOT NULL DEFAULT FALSE,
    srtp_mode VARCHAR(16) NOT NULL DEFAULT 'none'
        CHECK (srtp_mode IN ('none', 'sdes', 'dtls')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_providers_enabled_priority
    ON sip_trunk_providers(enabled, priority ASC);

-- sip_trunk_did_mappings: inbound DID-to-tenant routing rules
CREATE TABLE IF NOT EXISTS sip_trunk_did_mappings (
    id SERIAL PRIMARY KEY,
    trunk_provider_id INTEGER NOT NULL REFERENCES sip_trunk_providers(id) ON DELETE CASCADE,
    did_number VARCHAR(50) NOT NULL,
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    dispatcher_setid INTEGER NOT NULL DEFAULT 1,
    destination VARCHAR(255),
    description VARCHAR(255),
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (did_number, trunk_provider_id)
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_did_lookup
    ON sip_trunk_did_mappings(did_number, enabled, tenant_id)
    WHERE enabled = true;

CREATE INDEX IF NOT EXISTS idx_sip_trunk_did_mappings_tenant_id
    ON sip_trunk_did_mappings(tenant_id);

-- sip_trunk_registrations: runtime registration state for uac_registrant module
CREATE TABLE IF NOT EXISTS sip_trunk_registrations (
    id SERIAL PRIMARY KEY,
    trunk_provider_id INTEGER NOT NULL REFERENCES sip_trunk_providers(id) ON DELETE CASCADE,
    registrar VARCHAR(255) NOT NULL,
    proxy VARCHAR(255),
    aor VARCHAR(255) NOT NULL,
    third_party_registrant VARCHAR(255),
    username VARCHAR(255) NOT NULL,
    password BYTEA,
    binding_uri VARCHAR(255) NOT NULL,
    expiry INTEGER NOT NULL DEFAULT 3600 CHECK (expiry > 0),
    forced_socket VARCHAR(128),
    cluster_shtag VARCHAR(128),
    state INTEGER NOT NULL DEFAULT 0,
    last_register_sent TIMESTAMPTZ,
    last_register_succ TIMESTAMPTZ,
    last_register_code INTEGER,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_registrations_provider
    ON sip_trunk_registrations(trunk_provider_id);

-- CDR enrichment columns for Feature 014-C trunk-routed calls
ALTER TABLE cdr ADD COLUMN IF NOT EXISTS trunk_provider_id INTEGER;
ALTER TABLE cdr ADD COLUMN IF NOT EXISTS trunk_name VARCHAR(255);
ALTER TABLE cdr ADD COLUMN IF NOT EXISTS direction VARCHAR(16);

-- version tracking for OpenSIPS db_postgres compatibility
INSERT INTO version (table_name, table_version) VALUES
    ('sip_trunk_providers', 1),
    ('sip_trunk_did_mappings', 1),
    ('sip_trunk_registrations', 1)
ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;
