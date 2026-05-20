-- ============================================================
-- Feature 014-B: OCP Audit Log & Compliance Dashboard
-- Wave 1: Database Schema & Migrations
-- ============================================================

-- idempotent: ensure pgcrypto is available (needed for UUID type consistency)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- -----------------------------------------------------------
-- Table: ocp_audit_log
-- Append-only audit trail for OCP administrative actions.
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS ocp_audit_log (
    id              BIGSERIAL PRIMARY KEY,
    event_time      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    user_id         UUID,
    username        VARCHAR(64) NOT NULL,
    action          VARCHAR(64) NOT NULL,
    resource_type   VARCHAR(64),
    resource_id     VARCHAR(255),
    ip_address      INET NOT NULL,
    user_agent      VARCHAR(512),
    success         BOOLEAN NOT NULL DEFAULT TRUE,
    details         JSONB,
    prev_hash       VARCHAR(64),
    hash            VARCHAR(64) NOT NULL
);

-- Performance indexes
CREATE INDEX IF NOT EXISTS idx_ocp_audit_event_time
    ON ocp_audit_log(event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_user_id
    ON ocp_audit_log(user_id, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_action
    ON ocp_audit_log(action, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_resource
    ON ocp_audit_log(resource_type, resource_id, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_ip
    ON ocp_audit_log(ip_address, event_time);
CREATE INDEX IF NOT EXISTS idx_ocp_audit_details_gin
    ON ocp_audit_log USING GIN(details);

-- -----------------------------------------------------------
-- Immutability trigger
-- Blocks UPDATE and DELETE on ocp_audit_log unless executed
-- by the tsisip_retention service role.
-- -----------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_ocp_audit_log_immutable()
RETURNS TRIGGER AS $$
BEGIN
    IF (CURRENT_USER = 'tsisip_retention') THEN
        RETURN OLD;
    END IF;
    RAISE EXCEPTION 'Audit log entries are immutable';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS ocp_audit_log_immutable ON ocp_audit_log;
CREATE TRIGGER ocp_audit_log_immutable
    BEFORE UPDATE OR DELETE ON ocp_audit_log
    FOR EACH ROW
    EXECUTE FUNCTION fn_ocp_audit_log_immutable();

-- -----------------------------------------------------------
-- Role privileges
-- Create tsisip_retention role if absent; grant minimal perms.
-- Must be done before creating SECURITY DEFINER functions owned by it.
-- -----------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'tsisip_retention') THEN
        CREATE ROLE tsisip_retention WITH LOGIN;
    END IF;
END
$$;

-- -----------------------------------------------------------
-- Retention purge function
-- Accepts retention period in days; returns count of deleted rows.
-- SECURITY DEFINER so opensips caller executes as tsisip_retention,
-- bypassing the immutability trigger.
-- -----------------------------------------------------------
CREATE OR REPLACE FUNCTION ocp_audit_log_retention_purge(retention_days INTEGER)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM ocp_audit_log
    WHERE event_time < NOW() - INTERVAL '1 day' * retention_days;
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

ALTER FUNCTION ocp_audit_log_retention_purge OWNER TO tsisip_retention;

-- OCP application user: INSERT and SELECT only
GRANT INSERT, SELECT ON ocp_audit_log TO opensips;
GRANT USAGE, SELECT ON SEQUENCE ocp_audit_log_id_seq TO opensips;

-- Retention service role: DELETE required to bypass immutability trigger.
-- SELECT is also required so the BEFORE DELETE trigger can access OLD.
GRANT SELECT, DELETE ON ocp_audit_log TO tsisip_retention;
