-- ============================================================================
-- TSiSIP OCP Parity Schema Rollback
-- Reverts all tables and columns added by 04-ocp-parity-schema.sql
-- WARNING: This will DROP data. Use with caution.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Remove stub compatibility columns (reverse ALTER TABLE order)
-- ---------------------------------------------------------------------------
ALTER TABLE cc_agents DROP COLUMN IF EXISTS flowid;
ALTER TABLE cc_agents DROP COLUMN IF EXISTS enabled;
ALTER TABLE cc_flows DROP COLUMN IF EXISTS enabled;

ALTER TABLE dr_rules DROP COLUMN IF EXISTS enabled;
ALTER TABLE dr_gateways DROP COLUMN IF EXISTS enabled;

ALTER TABLE smpp DROP COLUMN IF EXISTS src_addr;
ALTER TABLE smpp DROP COLUMN IF EXISTS enabled;

-- ---------------------------------------------------------------------------
-- 2. Drop tables in dependency order (child tables first)
-- ---------------------------------------------------------------------------
DROP TABLE IF EXISTS cc_calls;
DROP TABLE IF EXISTS cc_agents;
DROP TABLE IF EXISTS cc_flows;

DROP TABLE IF EXISTS tviewer_schemas;
DROP TABLE IF EXISTS monit;
DROP TABLE IF EXISTS keepalived;

DROP TABLE IF EXISTS uacreg;
DROP TABLE IF EXISTS smpp;
DROP TABLE IF EXISTS status_report;
DROP TABLE IF EXISTS sockets;
DROP TABLE IF EXISTS sip_trace;
DROP TABLE IF EXISTS rtpproxy_sockets;
DROP TABLE IF EXISTS load_balancer;

DROP TABLE IF EXISTS dr_groups;
DROP TABLE IF EXISTS dr_rules;
DROP TABLE IF EXISTS dr_carriers;
DROP TABLE IF EXISTS dr_gateways;

DROP TABLE IF EXISTS config;
DROP TABLE IF EXISTS clusterer;
DROP TABLE IF EXISTS grp;
DROP TABLE IF EXISTS aliases;

-- ============================================================================
-- End of 04-ocp-parity-rollback.sql
-- ============================================================================
