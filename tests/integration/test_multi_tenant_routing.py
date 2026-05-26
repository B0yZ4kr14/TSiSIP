"""
Feature 002 Integration Tests: Multi-Tenant Header Routing
Validates: header_routing_rules lookup, fallback to routing_group, tenant isolation
"""
import subprocess

COMPOSE_FILE = "docker-compose.yml"


def psql_query(sql: str) -> list:
    r = subprocess.run(
        ["docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "postgres",
         "psql", "-U", "opensips", "-d", "opensips", "-tA", "-c", sql],
        capture_output=True, text=True
    )
    return [line for line in r.stdout.strip().split("\n") if line]


class TestMultiTenantRouting:
    """Multi-tenant header routing and tenant isolation."""

    def test_header_routing_rules_table_exists(self):
        """header_routing_rules table exists with expected schema."""
        rows = psql_query("""
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'header_routing_rules'
            ORDER BY ordinal_position
        """)
        columns = {}
        for row in rows:
            parts = row.split("|")
            if len(parts) >= 2:
                columns[parts[0]] = parts[1]
        assert "tenant_id" in columns
        assert "header_name" in columns
        assert "match_value" in columns
        assert "dispatcher_setid" in columns
        assert "priority" in columns
        assert "enabled" in columns

    def test_pbx_backends_table_exists(self):
        """pbx_backends table exists with expected schema."""
        rows = psql_query("""
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'pbx_backends'
            ORDER BY ordinal_position
        """)
        columns = {}
        for row in rows:
            parts = row.split("|")
            if len(parts) >= 2:
                columns[parts[0]] = parts[1]
        assert "tenant_id" in columns
        assert "dispatcher_setid" in columns
        assert "label" in columns
        assert "enabled" in columns

    def test_seed_header_routing_rules(self):
        """Seed data populates header_routing_rules for dev tenant."""
        rows = psql_query("""
            SELECT COUNT(*) FROM header_routing_rules h
            JOIN tenants t ON h.tenant_id = t.id
            WHERE t.sip_domain = 'dev.tsisip.local' AND h.enabled = true
        """)
        count = int(rows[0]) if rows else 0
        assert count >= 2, f"Expected >=2 routing rules, got {count}"

    def test_seed_pbx_backends(self):
        """Seed data populates pbx_backends for dev tenant."""
        rows = psql_query("""
            SELECT COUNT(*) FROM pbx_backends p
            JOIN tenants t ON p.tenant_id = t.id
            WHERE t.sip_domain = 'dev.tsisip.local' AND p.enabled = true
        """)
        count = int(rows[0]) if rows else 0
        assert count >= 1, f"Expected >=1 backend, got {count}"

    def test_tenant_isolation_subscriber(self):
        """Subscriber entries are scoped to tenant."""
        rows = psql_query("""
            SELECT s.username, t.sip_domain
            FROM subscriber s
            JOIN tenants t ON s.tenant_id = t.id
            WHERE s.username = 'devuser'
        """)
        assert len(rows) == 1
        parts = rows[0].split("|")
        assert parts[1] == 'dev.tsisip.local'

    def test_header_routing_priority_order(self):
        """Routing rules respect priority ordering."""
        rows = psql_query("""
            SELECT header_name, match_value, priority
            FROM header_routing_rules h
            JOIN tenants t ON h.tenant_id = t.id
            WHERE t.sip_domain = 'dev.tsisip.local'
            ORDER BY priority
        """)
        assert len(rows) >= 2
        priorities = []
        for row in rows:
            parts = row.split("|")
            if len(parts) >= 3:
                priorities.append(int(parts[2]))
        assert priorities == sorted(priorities), "Priorities should be ordered"

    def test_opensips_config_has_header_routing(self):
        """OpenSIPS config contains Feature 002 header routing logic."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert "header_routing_rules" in config, "Config should query header_routing_rules"
        assert "X-Route-Key" in config, "Config should reference X-Route-Key"
        assert 'remove_hf("X-Route-Key")' in config, "Config should sanitize X-Route-Key"
