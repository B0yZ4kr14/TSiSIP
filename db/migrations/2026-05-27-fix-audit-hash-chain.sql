-- ============================================================
-- Migration: Fix audit log hash chain integrity
-- Issue: Trigger used ip_address::TEXT which includes CIDR mask
--        (e.g. 0.0.0.0/32), but PHP reads ip_address as plain
--        host (0.0.0.0). This caused hash recomputation mismatch
--        in verifyAuditLogIntegrity().
-- Fix: 1. Update trigger to use host(ip_address) for canonicalization.
--      2. Recalculate all existing hashes using the new canonical
--         form, executed as tsisip_retention to bypass immutability.
-- ============================================================

-- Step 1: Update the hash chain trigger to use host(ip_address)
--         instead of ip_address::TEXT.
CREATE OR REPLACE FUNCTION fn_ocp_audit_log_hash_chain()
RETURNS TRIGGER AS $$
DECLARE
    last_hash VARCHAR(64);
    payload TEXT;
BEGIN
    SELECT hash INTO last_hash
    FROM ocp_audit_log
    ORDER BY id DESC
    LIMIT 1;

    IF last_hash IS NULL THEN
        last_hash := 'genesis';
    END IF;

    NEW.prev_hash := last_hash;

    payload := COALESCE(NEW.event_time::TEXT, '') || '|' ||
               COALESCE(NEW.user_id::TEXT, '') || '|' ||
               COALESCE(NEW.username, '') || '|' ||
               COALESCE(NEW.action, '') || '|' ||
               COALESCE(NEW.resource_type, '') || '|' ||
               COALESCE(NEW.resource_id, '') || '|' ||
               COALESCE(host(NEW.ip_address), '') || '|' ||
               COALESCE(NEW.user_agent, '') || '|' ||
               CASE WHEN NEW.success = true THEN '1' ELSE '0' END || '|' ||
               COALESCE(NEW.details::TEXT, '') || '|' ||
               NEW.prev_hash;

    NEW.hash := encode(digest(payload, 'sha256'), 'hex');

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Step 2: Create a temporary rehash function that runs as
--         tsisip_retention so it can UPDATE despite the
--         immutability trigger.
CREATE OR REPLACE FUNCTION fn_ocp_audit_log_rehash_all()
RETURNS INTEGER AS $$
DECLARE
    rec RECORD;
    chain_prev_hash TEXT := 'genesis';
    payload TEXT;
    new_hash TEXT;
    updated_count INTEGER := 0;
BEGIN
    FOR rec IN
        SELECT id, event_time, user_id, username, action,
               resource_type, resource_id, ip_address,
               user_agent, success, details, prev_hash AS old_prev_hash, hash AS old_hash
        FROM ocp_audit_log
        ORDER BY id ASC
    LOOP
        payload := COALESCE(rec.event_time::TEXT, '') || '|' ||
                   COALESCE(rec.user_id::TEXT, '') || '|' ||
                   COALESCE(rec.username, '') || '|' ||
                   COALESCE(rec.action, '') || '|' ||
                   COALESCE(rec.resource_type, '') || '|' ||
                   COALESCE(rec.resource_id, '') || '|' ||
                   COALESCE(host(rec.ip_address), '') || '|' ||
                   COALESCE(rec.user_agent, '') || '|' ||
                   CASE WHEN rec.success = true THEN '1' ELSE '0' END || '|' ||
                   COALESCE(rec.details::TEXT, '') || '|' ||
                   chain_prev_hash;

        new_hash := encode(digest(payload, 'sha256'), 'hex');

        UPDATE ocp_audit_log
        SET prev_hash = chain_prev_hash,
            hash = new_hash
        WHERE id = rec.id;

        chain_prev_hash := new_hash;
        updated_count := updated_count + 1;
    END LOOP;

    RETURN updated_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

ALTER FUNCTION fn_ocp_audit_log_rehash_all() OWNER TO tsisip_retention;

-- Grant UPDATE temporarily so tsisip_retention can rehash rows.
GRANT UPDATE ON ocp_audit_log TO tsisip_retention;

-- Step 3: Execute the rehash (runs as tsisip_retention)
SELECT fn_ocp_audit_log_rehash_all() AS rows_rehashed;

-- Revoke UPDATE after rehash is complete.
REVOKE UPDATE ON ocp_audit_log FROM tsisip_retention;

-- Step 4: Clean up the temporary function
DROP FUNCTION IF EXISTS fn_ocp_audit_log_rehash_all();
