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
    ADD COLUMN IF NOT EXISTS tenant_id VARCHAR(36) NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000',
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
    tenant_id VARCHAR(36) NOT NULL,
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
    tenant_id VARCHAR(36) NOT NULL,
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
    tenant_id VARCHAR(36),
    dispatcher_setid INTEGER,
    backend_label VARCHAR(128),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cdr_call_id ON cdr(call_id);
CREATE INDEX IF NOT EXISTS idx_cdr_call_start ON cdr(call_start);
CREATE INDEX IF NOT EXISTS idx_cdr_from_user ON cdr(from_user);
CREATE INDEX IF NOT EXISTS idx_cdr_tenant ON cdr(tenant_id);
CREATE INDEX IF NOT EXISTS idx_cdr_status ON cdr(call_status);

-- ocp_users: administrative users for TSiSIP Control Panel
CREATE TABLE IF NOT EXISTS ocp_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'readonly'
        CHECK (role IN ('admin', 'devops', 'dentist', 'assistant', 'user', 'readonly')),
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    failed_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until TIMESTAMPTZ,
    last_login_at TIMESTAMPTZ,
    force_password_change BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_ocp_users_enabled
    ON ocp_users(username, enabled)
    WHERE enabled = true;

-- ocp_login_log: audit trail for OCP authentication events
CREATE TABLE IF NOT EXISTS ocp_login_log (
    id BIGSERIAL PRIMARY KEY,
    event_time TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    username VARCHAR(64) NOT NULL,
    source_ip INET NOT NULL,
    user_agent VARCHAR(512),
    result VARCHAR(32) NOT NULL,
    reason VARCHAR(255)
);

CREATE INDEX IF NOT EXISTS idx_ocp_login_event_time
    ON ocp_login_log(event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_login_username
    ON ocp_login_log(username, event_time);

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

-- ocp_password_changes: dedicated audit table for password change events
-- Required by security_constitution.md section 7 (Audit & Logging)
CREATE TABLE IF NOT EXISTS ocp_password_changes (
    id              SERIAL PRIMARY KEY,
    event_time      TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL,
    user_id         UUID NOT NULL,
    username        VARCHAR(64) NOT NULL,
    changed_by      UUID NOT NULL,
    changed_by_name VARCHAR(64) NOT NULL,
    source_ip       INET NOT NULL,
    user_agent      VARCHAR(512),
    success         BOOLEAN NOT NULL DEFAULT TRUE,
    failure_reason  VARCHAR(255)
);

CREATE INDEX IF NOT EXISTS idx_ocp_password_changes_time
    ON ocp_password_changes(event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_password_changes_user
    ON ocp_password_changes(user_id, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_password_changes_username
    ON ocp_password_changes(username, event_time);

-- ============================================================================
-- Feature 017: SIP Trunk Provider Integration
-- ============================================================================
-- These tables and triggers were added to production schema but were missing
-- from the repository init scripts. Synchronized from VPS production schema.

CREATE TABLE IF NOT EXISTS sip_trunk_providers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    host VARCHAR(255) NOT NULL,
    port INTEGER DEFAULT 5060 NOT NULL CHECK (port > 0 AND port <= 65535),
    transport VARCHAR(10) DEFAULT 'udp' NOT NULL
        CHECK (transport IN ('udp', 'tcp', 'tls', 'ws', 'wss')),
    auth_username VARCHAR(255),
    auth_password_encrypted BYTEA,
    from_domain VARCHAR(255),
    caller_id_prefix VARCHAR(50),
    priority INTEGER DEFAULT 100 NOT NULL,
    enabled BOOLEAN DEFAULT TRUE NOT NULL,
    registration_required BOOLEAN DEFAULT FALSE NOT NULL,
    registration_expiry INTEGER DEFAULT 3600 NOT NULL CHECK (registration_expiry > 0),
    max_cps INTEGER DEFAULT 10 NOT NULL CHECK (max_cps > 0),
    max_concurrent INTEGER DEFAULT 100 NOT NULL CHECK (max_concurrent > 0),
    require_mtls BOOLEAN DEFAULT FALSE NOT NULL,
    srtp_mode VARCHAR(16) DEFAULT 'none' NOT NULL
        CHECK (srtp_mode IN ('none', 'sdes', 'dtls')),
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT NOW() NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_providers_enabled_priority
    ON sip_trunk_providers(enabled, priority);

CREATE TABLE IF NOT EXISTS sip_trunk_did_mappings (
    id SERIAL PRIMARY KEY,
    trunk_provider_id INTEGER NOT NULL REFERENCES sip_trunk_providers(id) ON DELETE CASCADE,
    did_number VARCHAR(50) NOT NULL,
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    dispatcher_setid INTEGER DEFAULT 1 NOT NULL,
    destination VARCHAR(255),
    description VARCHAR(255),
    enabled BOOLEAN DEFAULT TRUE NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    UNIQUE (did_number, trunk_provider_id)
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_did_lookup
    ON sip_trunk_did_mappings(did_number, enabled, tenant_id) WHERE enabled = true;
CREATE INDEX IF NOT EXISTS idx_sip_trunk_did_mappings_tenant_id
    ON sip_trunk_did_mappings(tenant_id);

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
    expiry INTEGER DEFAULT 3600 NOT NULL CHECK (expiry > 0),
    forced_socket VARCHAR(128),
    cluster_shtag VARCHAR(128),
    state INTEGER DEFAULT 0 NOT NULL,
    last_register_sent TIMESTAMPTZ,
    last_register_succ TIMESTAMPTZ,
    last_register_code INTEGER,
    created_at TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    binding_params VARCHAR(255)
);

CREATE INDEX IF NOT EXISTS idx_sip_trunk_registrations_provider
    ON sip_trunk_registrations(trunk_provider_id);

-- Trigger: auto-sync trunk providers to dispatcher set 100
CREATE OR REPLACE FUNCTION sync_trunk_providers_to_dispatcher()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        IF NEW.enabled = true THEN
            INSERT INTO dispatcher (setid, destination, state, weight, priority, attrs, description)
            VALUES (
                100,
                'sip:' || NEW.host || ':' || NEW.port,
                0,
                '1',
                NEW.priority,
                'ping_interval=30;ping_from=sip:healthcheck@tsisip',
                'Trunk: ' || NEW.name
            );
        END IF;
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        DELETE FROM dispatcher WHERE setid = 100 AND description = 'Trunk: ' || OLD.name;
        IF NEW.enabled = true THEN
            INSERT INTO dispatcher (setid, destination, state, weight, priority, attrs, description)
            VALUES (
                100,
                'sip:' || NEW.host || ':' || NEW.port,
                0,
                '1',
                NEW.priority,
                'ping_interval=30;ping_from=sip:healthcheck@tsisip',
                'Trunk: ' || NEW.name
            );
        END IF;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        DELETE FROM dispatcher WHERE setid = 100 AND description = 'Trunk: ' || OLD.name;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trunk_provider_dispatcher_sync ON sip_trunk_providers;
CREATE TRIGGER trunk_provider_dispatcher_sync
    AFTER INSERT OR DELETE OR UPDATE ON sip_trunk_providers
    FOR EACH ROW EXECUTE FUNCTION sync_trunk_providers_to_dispatcher();

-- Feature 017: Auto-populate sip_trunk_registrations from sip_trunk_providers
CREATE OR REPLACE FUNCTION sync_trunk_registrations()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        IF NEW.registration_required = true THEN
            INSERT INTO sip_trunk_registrations (
                trunk_provider_id, registrar, proxy, aor, username,
                password, binding_uri, expiry, state
            ) VALUES (
                NEW.id,
                'sip:' || NEW.host || ':' || NEW.port,
                NULL,
                'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                COALESCE(NEW.auth_username, NEW.name),
                NEW.auth_password_encrypted,
                'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                NEW.registration_expiry,
                0
            );
        END IF;
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        IF NEW.registration_required = true AND OLD.registration_required = false THEN
            INSERT INTO sip_trunk_registrations (
                trunk_provider_id, registrar, proxy, aor, username,
                password, binding_uri, expiry, state
            ) VALUES (
                NEW.id,
                'sip:' || NEW.host || ':' || NEW.port,
                NULL,
                'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                COALESCE(NEW.auth_username, NEW.name),
                NEW.auth_password_encrypted,
                'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                NEW.registration_expiry,
                0
            );
        ELSIF NEW.registration_required = false AND OLD.registration_required = true THEN
            DELETE FROM sip_trunk_registrations WHERE trunk_provider_id = OLD.id;
        ELSIF NEW.registration_required = true THEN
            UPDATE sip_trunk_registrations SET
                registrar = 'sip:' || NEW.host || ':' || NEW.port,
                aor = 'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                username = COALESCE(NEW.auth_username, NEW.name),
                password = NEW.auth_password_encrypted,
                binding_uri = 'sip:' || COALESCE(NEW.auth_username, NEW.name) || '@' || NEW.host,
                expiry = NEW.registration_expiry
            WHERE trunk_provider_id = NEW.id;
        END IF;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        DELETE FROM sip_trunk_registrations WHERE trunk_provider_id = OLD.id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trunk_registration_sync ON sip_trunk_providers;
CREATE TRIGGER trunk_registration_sync
    AFTER INSERT OR UPDATE OR DELETE ON sip_trunk_providers
    FOR EACH ROW
    EXECUTE FUNCTION sync_trunk_registrations();

-- ============================================================================
-- Feature 030: Password Policy — History & Expiration
-- ============================================================================

-- Track password hash history for reuse prevention
CREATE TABLE IF NOT EXISTS ocp_password_history (
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL REFERENCES ocp_users(id) ON DELETE CASCADE,
    password_hash VARCHAR(255) NOT NULL,
    changed_at  TIMESTAMP WITH TIME ZONE DEFAULT NOW() NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_ocp_password_history_user
    ON ocp_password_history(user_id, changed_at DESC);

-- Add password change timestamp to users table
ALTER TABLE ocp_users
    ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP WITH TIME ZONE;
