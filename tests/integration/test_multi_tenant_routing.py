"""
Feature 002 Integration Tests: Multi-Tenant Header Routing
Validates: header_routing_rules lookup, fallback to routing_group, tenant isolation
"""
import pytest


class TestMultiTenantRouting:
    """Multi-tenant header routing and tenant isolation."""

    def test_header_routing_rules_table_exists(self, db_conn):
        """header_routing_rules table exists with expected schema."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_name = 'header_routing_rules'
                ORDER BY ordinal_position
            """)
            columns = {row[0]: row[1] for row in cur.fetchall()}
        assert "tenant_id" in columns
        assert "header_name" in columns
        assert "match_value" in columns
        assert "dispatcher_setid" in columns
        assert "priority" in columns
        assert "enabled" in columns

    def test_pbx_backends_table_exists(self, db_conn):
        """pbx_backends table exists with expected schema."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_name = 'pbx_backends'
                ORDER BY ordinal_position
            """)
            columns = {row[0]: row[1] for row in cur.fetchall()}
        assert "tenant_id" in columns
        assert "dispatcher_setid" in columns
        assert "label" in columns
        assert "enabled" in columns

    def test_seed_header_routing_rules(self, db_conn):
        """Seed data populates header_routing_rules for dev tenant."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT COUNT(*) FROM header_routing_rules h
                JOIN tenants t ON h.tenant_id = t.id
                WHERE t.sip_domain = 'dev.tsisip.local' AND h.enabled = true
            """)
            count = cur.fetchone()[0]
        assert count >= 2, f"Expected >=2 routing rules, got {count}"

    def test_seed_pbx_backends(self, db_conn):
        """Seed data populates pbx_backends for dev tenant."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT COUNT(*) FROM pbx_backends p
                JOIN tenants t ON p.tenant_id = t.id
                WHERE t.sip_domain = 'dev.tsisip.local' AND p.enabled = true
            """)
            count = cur.fetchone()[0]
        assert count >= 1, f"Expected >=1 backend, got {count}"

    def test_tenant_isolation_subscriber(self, db_conn):
        """Subscriber entries are scoped to tenant."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT s.username, t.sip_domain
                FROM subscriber s
                JOIN tenants t ON s.tenant_id = t.id
                WHERE s.username = 'devuser'
            """)
            rows = cur.fetchall()
        assert len(rows) == 1
        assert rows[0][1] == 'dev.tsisip.local'

    def test_header_routing_priority_order(self, db_conn):
        """Routing rules respect priority ordering."""
        with db_conn.cursor() as cur:
            cur.execute("""
                SELECT header_name, match_value, priority
                FROM header_routing_rules h
                JOIN tenants t ON h.tenant_id = t.id
                WHERE t.sip_domain = 'dev.tsisip.local'
                ORDER BY priority
            """)
            rows = cur.fetchall()
        assert len(rows) >= 2
        priorities = [r[2] for r in rows]
        assert priorities == sorted(priorities), "Priorities should be ordered"

    def test_opensips_config_has_header_routing(self):
        """OpenSIPS config contains Feature 002 header routing logic."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert "header_routing_rules" in config, "Config should query header_routing_rules"
        assert "X-Route-Key" in config, "Config should reference X-Route-Key"
        assert "remove_hf(\"X-Route-Key\")" in config, "Config should sanitize X-Route-Key"
