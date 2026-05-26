-- TSiSIP Schema Consistency Patch
-- Brownfield M2 Resolution: Eliminate db_postgres UUID warning (OID 2950)
--
-- Context: OpenSIPS db_postgres module does not natively understand PostgreSQL's
-- UUID type (OID 2950). It falls back to DB_STRING with a log warning. While
-- functionally harmless, this creates log noise. Rather than altering the
-- tenants.id primary key (which uses gen_random_uuid()), we cast the UUID
-- to VARCHAR(36) at query time in opensips.cfg.tpl.
--
-- This migration script documents the canonical decision. No schema ALTER is
-- required because the cast is performed in the OpenSIPS SQL query.
--
-- Affected query (opensips.cfg.tpl line ~893):
--   SELECT tenant_id::VARCHAR(36), dispatcher_setid FROM sip_trunk_did_mappings ...
--
-- If future tables introduce UUID columns read by OpenSIPS, apply the same
-- ::VARCHAR(36) cast pattern rather than changing PostgreSQL native types.

-- Verify current schema consistency
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'sip_trunk_did_mappings'
        AND column_name = 'tenant_id'
        AND data_type = 'uuid'
    ) THEN
        RAISE NOTICE 'sip_trunk_did_mappings.tenant_id is UUID — expected. Cast to VARCHAR(36) is applied in OpenSIPS query.';
    END IF;
END $$;
