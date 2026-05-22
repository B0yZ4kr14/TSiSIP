"""
Feature 001 Integration Tests: CDR / Billing Foundation
Validates: cdr table schema, acc module configuration, CDR logging
"""
import pytest


class TestCDRBilling:
    """Call Detail Records and billing foundation."""

    def test_cdr_table_exists(self, db_conn):
        """cdr table exists with expected schema."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_name = 'cdr'
                ORDER BY ordinal_position
            """)
            columns = {row[0]: row[1] for row in cur.fetchall()}
        assert "call_id" in columns
        assert "call_start" in columns
        assert "duration" in columns
        assert "from_user" in columns
        assert "to_user" in columns
        assert "call_status" in columns
        assert "tenant_id" in columns

    def test_cdr_indexes_exist(self, db_conn):
        """cdr table has required indexes."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT indexname
                FROM pg_indexes
                WHERE tablename = 'cdr'
            """)
            indexes = {row[0] for row in cur.fetchall()}
        assert "idx_cdr_call_id" in indexes
        assert "idx_cdr_call_start" in indexes
        assert "idx_cdr_tenant" in indexes

    def test_opensips_config_has_acc_module(self):
        """OpenSIPS config loads acc module."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert 'loadmodule "acc.so"' in config
        assert 'modparam("acc", "db_table_acc"' in config
        assert "cdr" in config

    def test_opensips_config_has_acc_flag(self):
        """OpenSIPS sets accounting flag on INVITE."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert "setflag(1)" in config

    def test_dockerfile_has_acc_module(self):
        """Dockerfile includes acc module in build."""
        with open("Dockerfile") as f:
            dockerfile = f.read()
        assert "acc" in dockerfile

    def test_cdr_insert(self, db_conn):
        """cdr table accepts insert."""
        with db_conn.cursor() as cur:
            cur.execute("""
                INSERT INTO cdr (call_id, from_user, from_domain, to_user, to_domain, source_ip, sip_method, call_status, tenant_id)
                VALUES ('test-call-001', 'devuser', 'dev.tsisip.local', '1000', 'dev.tsisip.local', '192.0.2.1', 'INVITE', 'completed', '00000000-0000-0000-0000-000000000000')
            """)
            cur.execute("SELECT COUNT(*) FROM cdr WHERE call_id = 'test-call-001'")
            count = cur.fetchone()[0]
        assert count == 1
        # Cleanup
        with db_conn.cursor() as cur:
            cur.execute("DELETE FROM cdr WHERE call_id = 'test-call-001'")
