-- ============================================================================
-- TSiSIP OCP Parity Schema Migration
-- Adds all missing OpenSIPS 3.6 LTS tables required for OCP v9.3.6 parity
-- Idempotent: safe to re-run via docker-entrypoint-initdb.d
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. ALIASES (alias_db module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS aliases (
    id          SERIAL PRIMARY KEY,
    alias_username  VARCHAR(64) NOT NULL,
    alias_domain    VARCHAR(64) NOT NULL DEFAULT '',
    username        VARCHAR(64) NOT NULL,
    domain          VARCHAR(64) NOT NULL DEFAULT '',
    last_modified   TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_aliases_alias ON aliases(alias_username, alias_domain);
CREATE INDEX IF NOT EXISTS idx_aliases_user ON aliases(username, domain);
COMMENT ON TABLE aliases IS 'SIP alias mappings for alias_db module (OCP v9)';

-- ---------------------------------------------------------------------------
-- 2. GROUPS (group module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS grp (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(64) NOT NULL,
    domain      VARCHAR(64) NOT NULL DEFAULT '',
    grp         VARCHAR(64) NOT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_grp_user ON grp(username, domain);
CREATE INDEX IF NOT EXISTS idx_grp_name ON grp(grp);
COMMENT ON TABLE grp IS 'Group-based ACL for SIP subscribers (OCP v9)';

-- ---------------------------------------------------------------------------
-- 3. CALL CENTER (call_center module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cc_flows (
    id          SERIAL PRIMARY KEY,
    flowid      VARCHAR(64) NOT NULL UNIQUE,
    priority    INTEGER NOT NULL DEFAULT 0,
    skill       VARCHAR(128) NOT NULL DEFAULT '',
    cid         VARCHAR(64) NOT NULL DEFAULT '',
    max_wrapup_time INTEGER DEFAULT 0,
    dissuading_quantity INTEGER DEFAULT 0,
    dissuading_duration INTEGER DEFAULT 0,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE cc_flows IS 'Call Center flows (OCP v9)';

CREATE TABLE IF NOT EXISTS cc_agents (
    id          SERIAL PRIMARY KEY,
    agentid     VARCHAR(64) NOT NULL UNIQUE,
    location    VARCHAR(128) NOT NULL DEFAULT '',
    skills      VARCHAR(255) NOT NULL DEFAULT '',
    logstate    INTEGER NOT NULL DEFAULT 0,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE cc_agents IS 'Call Center agents (OCP v9)';

CREATE TABLE IF NOT EXISTS cc_calls (
    id          SERIAL PRIMARY KEY,
    callid      VARCHAR(128) NOT NULL,
    state       INTEGER NOT NULL DEFAULT 0,
    flowid      VARCHAR(64) NOT NULL,
    agentid     VARCHAR(64),
    fstime      TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    answered    TIMESTAMP WITHOUT TIME ZONE,
    ended       TIMESTAMP WITHOUT TIME ZONE,
    caller      VARCHAR(128) NOT NULL DEFAULT '',
    called      VARCHAR(128) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS idx_cc_calls_flow ON cc_calls(flowid);
CREATE INDEX IF NOT EXISTS idx_cc_calls_agent ON cc_calls(agentid);
COMMENT ON TABLE cc_calls IS 'Call Center active/historical calls (OCP v9)';

-- ---------------------------------------------------------------------------
-- 4. CLUSTERER (clusterer module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clusterer (
    id          SERIAL PRIMARY KEY,
    cluster_id  INTEGER NOT NULL,
    node_id     INTEGER NOT NULL,
    url         VARCHAR(128) NOT NULL,
    state       INTEGER NOT NULL DEFAULT 1,
    no_ping_retries INTEGER DEFAULT 3,
    priority    INTEGER DEFAULT 0,
    sip_addr    VARCHAR(128),
    flags       INTEGER DEFAULT 0,
    description VARCHAR(256),
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(cluster_id, node_id)
);
CREATE INDEX IF NOT EXISTS idx_clusterer_cluster ON clusterer(cluster_id);
COMMENT ON TABLE clusterer IS 'OpenSIPS clusterer node definitions (OCP v9)';

-- ---------------------------------------------------------------------------
-- 5. CONFIG TABLE (cfgutils / config module — OCP 9.3.5+)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS config (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(64) NOT NULL UNIQUE,
    value       TEXT NOT NULL,
    category    VARCHAR(32) DEFAULT 'general',
    description TEXT,
    updated_at  TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_config_category ON config(category);
COMMENT ON TABLE config IS 'Runtime configuration table for OpenSIPS cfgutils (OCP 9.3.5+)';

-- ---------------------------------------------------------------------------
-- 6. DYNAMIC ROUTING (drouting module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dr_gateways (
    gwid        SERIAL PRIMARY KEY,
    type        INTEGER DEFAULT 0,
    address     VARCHAR(128) NOT NULL,
    strip       INTEGER DEFAULT 0,
    pri_prefix  VARCHAR(64) DEFAULT NULL,
    attrs       VARCHAR(128) DEFAULT NULL,
    probe_mode  INTEGER DEFAULT 0,
    state       INTEGER DEFAULT 0,
    socket      VARCHAR(128) DEFAULT NULL,
    description VARCHAR(256) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE dr_gateways IS 'Dynamic Routing gateways (OCP v9)';

CREATE TABLE IF NOT EXISTS dr_carriers (
    id          SERIAL PRIMARY KEY,
    carrierid   VARCHAR(64) NOT NULL UNIQUE,
    gwlist      VARCHAR(255) NOT NULL,
    flags       INTEGER DEFAULT 0,
    state       INTEGER DEFAULT 0,
    attrs       VARCHAR(128) DEFAULT NULL,
    description VARCHAR(256) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE dr_carriers IS 'Dynamic Routing carriers (OCP v9)';

CREATE TABLE IF NOT EXISTS dr_rules (
    ruleid      SERIAL PRIMARY KEY,
    groupid     VARCHAR(64) NOT NULL DEFAULT 'default',
    prefix      VARCHAR(64) NOT NULL DEFAULT '',
    timerec     VARCHAR(255) DEFAULT NULL,
    priority    INTEGER NOT NULL DEFAULT 0,
    routeid     VARCHAR(64) DEFAULT NULL,
    gwlist      VARCHAR(255) NOT NULL,
    sort_alg    VARCHAR(8) DEFAULT 'N',
    sort_profile VARCHAR(64) DEFAULT NULL,
    attrs       VARCHAR(128) DEFAULT NULL,
    description VARCHAR(256) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_dr_rules_group ON dr_rules(groupid);
CREATE INDEX IF NOT EXISTS idx_dr_rules_prefix ON dr_rules(prefix);
COMMENT ON TABLE dr_rules IS 'Dynamic Routing rules (OCP v9)';

CREATE TABLE IF NOT EXISTS dr_groups (
    id          SERIAL PRIMARY KEY,
    username    VARCHAR(64) NOT NULL,
    domain      VARCHAR(64) NOT NULL DEFAULT '',
    groupid     VARCHAR(64) NOT NULL DEFAULT 'default',
    description VARCHAR(256) DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_dr_groups_user ON dr_groups(username, domain);
COMMENT ON TABLE dr_groups IS 'Dynamic Routing subscriber groups (OCP v9)';

-- ---------------------------------------------------------------------------
-- 7. LOAD BALANCER (load_balancer module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS load_balancer (
    id          SERIAL PRIMARY KEY,
    group_id    INTEGER NOT NULL,
    dst_uri     VARCHAR(128) NOT NULL,
    resources   VARCHAR(255) NOT NULL,
    probe_mode  INTEGER DEFAULT 0,
    description VARCHAR(256) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_lb_group ON load_balancer(group_id);
COMMENT ON TABLE load_balancer IS 'Load Balancer destinations (OCP v9)';

-- ---------------------------------------------------------------------------
-- 8. RTPPROXY (rtpproxy module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rtpproxy_sockets (
    id          SERIAL PRIMARY KEY,
    rtpproxy_sock VARCHAR(128) NOT NULL UNIQUE,
    set_id      INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_rtpproxy_set ON rtpproxy_sockets(set_id);
COMMENT ON TABLE rtpproxy_sockets IS 'RTPProxy socket definitions (OCP v9)';

-- ---------------------------------------------------------------------------
-- 9. SIPTRACE (siptrace module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sip_trace (
    id          BIGSERIAL PRIMARY KEY,
    time_stamp  TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    time_us     INTEGER DEFAULT 0,
    callid      VARCHAR(255) NOT NULL,
    traced_user VARCHAR(128) DEFAULT NULL,
    msg         TEXT NOT NULL,
    method      VARCHAR(64) DEFAULT NULL,
    status      VARCHAR(64) DEFAULT NULL,
    fromip      VARCHAR(64) DEFAULT NULL,
    toip        VARCHAR(64) DEFAULT NULL,
    fromtag     VARCHAR(128) DEFAULT NULL,
    direction   VARCHAR(8) DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_siptrace_callid ON sip_trace(callid);
CREATE INDEX IF NOT EXISTS idx_siptrace_time ON sip_trace(time_stamp);
CREATE INDEX IF NOT EXISTS idx_siptrace_method ON sip_trace(method);
COMMENT ON TABLE sip_trace IS 'SIP packet capture storage (OCP v9)';

-- ---------------------------------------------------------------------------
-- 10. SOCKETS MANAGEMENT (OCP 9.3.6+)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sockets (
    id          SERIAL PRIMARY KEY,
    proto       VARCHAR(8) NOT NULL,
    address     VARCHAR(128) NOT NULL,
    port        INTEGER NOT NULL,
    options     VARCHAR(255) DEFAULT NULL,
    description VARCHAR(256) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE sockets IS 'Dynamic socket provisioning (OCP 9.3.6+)';

-- ---------------------------------------------------------------------------
-- 11. STATUS REPORT (status_report module — OCP 9.3.3+)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS status_report (
    id          SERIAL PRIMARY KEY,
    identifier  VARCHAR(128) NOT NULL,
    severity    VARCHAR(16) NOT NULL DEFAULT 'info',
    timestamp   TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    details     TEXT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_status_id ON status_report(identifier);
CREATE INDEX IF NOT EXISTS idx_status_severity ON status_report(severity);
COMMENT ON TABLE status_report IS 'OpenSIPS status report identifiers (OCP 9.3.3+)';

-- ---------------------------------------------------------------------------
-- 12. SMPP GATEWAY (smpp module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS smpp (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(64) NOT NULL UNIQUE,
    ip          VARCHAR(128) NOT NULL,
    port        INTEGER NOT NULL,
    system_id   VARCHAR(64) NOT NULL,
    password    VARCHAR(64) NOT NULL,
    system_type VARCHAR(64) DEFAULT NULL,
    src_ton     INTEGER DEFAULT 0,
    src_npi     INTEGER DEFAULT 0,
    dst_ton     INTEGER DEFAULT 0,
    dst_npi     INTEGER DEFAULT 0,
    session_type VARCHAR(16) DEFAULT 'TX',
    alt_dcs     INTEGER DEFAULT 0,
    dlr_timeout INTEGER DEFAULT 0,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE smpp IS 'SMPP Gateway SMSC definitions (OCP v9)';

-- ---------------------------------------------------------------------------
-- 13. UAC REGISTRANT (uac_registrant module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS uacreg (
    id          SERIAL PRIMARY KEY,
    l_uuid      VARCHAR(64) NOT NULL UNIQUE,
    l_username  VARCHAR(64) NOT NULL,
    l_domain    VARCHAR(64) NOT NULL DEFAULT '',
    r_username  VARCHAR(64) NOT NULL,
    r_domain    VARCHAR(64) NOT NULL,
    realm       VARCHAR(64) NOT NULL DEFAULT '',
    auth_username VARCHAR(64) NOT NULL DEFAULT '',
    auth_password VARCHAR(64) NOT NULL DEFAULT '',
    auth_proxy  VARCHAR(128) NOT NULL,
    expires     INTEGER NOT NULL DEFAULT 3600,
    flags       INTEGER NOT NULL DEFAULT 0,
    reg_delay   INTEGER NOT NULL DEFAULT 0,
    contact_addr VARCHAR(128) DEFAULT NULL,
    socket      VARCHAR(128) DEFAULT NULL,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_uacreg_uuid ON uacreg(l_uuid);
COMMENT ON TABLE uacreg IS 'UAC client registrations (OCP v9)';

-- ============================================================================
-- End of 04-ocp-parity-schema.sql
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 14. KEEPALIVED (keepalived module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS keepalived (
    id          SERIAL PRIMARY KEY,
    vrrp_id     INTEGER NOT NULL,
    state       VARCHAR(16) NOT NULL DEFAULT 'backup',
    priority    INTEGER NOT NULL DEFAULT 100,
    interface   VARCHAR(64) NOT NULL,
    virtual_ip  INET NOT NULL,
    enabled     INTEGER NOT NULL DEFAULT 1,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    UNIQUE(vrrp_id, interface)
);
CREATE INDEX IF NOT EXISTS idx_keepalived_vip ON keepalived(virtual_ip);
COMMENT ON TABLE keepalived IS 'Keepalived VRRP instance definitions (OCP v9)';

-- ---------------------------------------------------------------------------
-- 15. MONIT (monit module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS monit (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(64) NOT NULL,
    process_name VARCHAR(64) NOT NULL,
    check_type  VARCHAR(32) NOT NULL DEFAULT 'process',
    alert_email VARCHAR(128) DEFAULT NULL,
    enabled     INTEGER NOT NULL DEFAULT 1,
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_monit_name ON monit(name);
CREATE INDEX IF NOT EXISTS idx_monit_proc ON monit(process_name);
COMMENT ON TABLE monit IS 'Monit process monitoring checks (OCP v9)';

-- ---------------------------------------------------------------------------
-- 16. TVIEWER (tviewer module)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tviewer_schemas (
    id          SERIAL PRIMARY KEY,
    table_name  VARCHAR(64) NOT NULL UNIQUE,
    columns     VARCHAR(512) NOT NULL DEFAULT '*',
    primary_key VARCHAR(64) DEFAULT 'id',
    description VARCHAR(256) DEFAULT NULL,
    enabled     INTEGER NOT NULL DEFAULT 1,
    created_at  TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    updated_at  TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);
COMMENT ON TABLE tviewer_schemas IS 'TViewer generic table schema definitions (OCP v9)';

-- ============================================================================
-- Schema extensions for PHP stub compatibility
-- ============================================================================

-- Add enabled/src_addr to smpp for OCP stub compatibility
ALTER TABLE smpp ADD COLUMN IF NOT EXISTS enabled INTEGER NOT NULL DEFAULT 1;
ALTER TABLE smpp ADD COLUMN IF NOT EXISTS src_addr VARCHAR(64) DEFAULT NULL;

-- ============================================================================
-- End of OCP Parity Schema Extensions
-- ============================================================================

-- ============================================================================
-- Schema corrections for PHP stub compatibility
-- ============================================================================

-- Add enabled to dr_gateways if missing (PHP stub compatibility)
ALTER TABLE dr_gateways ADD COLUMN IF NOT EXISTS enabled INTEGER NOT NULL DEFAULT 1;

-- Add enabled to dr_rules if missing (PHP stub compatibility)
ALTER TABLE dr_rules ADD COLUMN IF NOT EXISTS enabled INTEGER NOT NULL DEFAULT 1;

-- ============================================================================
-- End of corrections
-- ============================================================================

-- Call Center stub compatibility
ALTER TABLE cc_flows ADD COLUMN IF NOT EXISTS enabled INTEGER NOT NULL DEFAULT 1;
ALTER TABLE cc_agents ADD COLUMN IF NOT EXISTS enabled INTEGER NOT NULL DEFAULT 1;
ALTER TABLE cc_agents ADD COLUMN IF NOT EXISTS flowid VARCHAR(64) DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_cc_agents_flow ON cc_agents(flowid);
