"""
Feature 001 Integration Tests: CDR / Billing Foundation
Validates: cdr table schema, acc module configuration, CDR logging
"""
import os
import subprocess
import pytest

COMPOSE_FILE = os.environ.get("COMPOSE_FILE", "docker-compose.yml")


def _psql(sql: str) -> tuple[int, str, str]:
    """Run a SQL query inside the postgres container and return (rc, stdout, stderr)."""
    r = subprocess.run(
        [
            "docker", "compose", "-f", COMPOSE_FILE, "exec", "-T", "postgres",
            "psql", "-U", "opensips", "-d", "opensips", "-tAc", sql,
        ],
        capture_output=True, text=True,
    )
    return r.returncode, r.stdout, r.stderr


class TestCDRBilling:
    """Call Detail Records and billing foundation."""

    def test_cdr_table_exists(self):
        """cdr table exists with expected schema."""
        rc, out, err = _psql(
            "SELECT column_name, data_type FROM information_schema.columns "
            "WHERE table_name = 'cdr' ORDER BY ordinal_position"
        )
        assert rc == 0, f"Query failed: {err}"
        columns = {line.split("|")[0].strip(): line.split("|")[1].strip()
                   for line in out.strip().split("\n") if "|" in line}
        assert "call_id" in columns
        assert "call_start" in columns
        assert "duration" in columns
        assert "from_user" in columns
        assert "to_user" in columns
        assert "call_status" in columns
        assert "tenant_id" in columns

    def test_cdr_indexes_exist(self):
        """cdr table has required indexes."""
        rc, out, err = _psql(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'cdr'"
        )
        assert rc == 0, f"Query failed: {err}"
        indexes = {line.strip() for line in out.strip().split("\n") if line.strip()}
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
        """OpenSIPS 3.6 uses do_accounting() instead of setflag(1) for CDR."""
        with open("opensips/opensips.cfg.tpl") as f:
            config = f.read()
        assert 'do_accounting("db", "cdr")' in config

    def test_dockerfile_has_acc_module(self):
        """Dockerfile includes acc module in build."""
        with open("Dockerfile") as f:
            dockerfile = f.read()
        assert "acc" in dockerfile

    def test_cdr_insert(self):
        """cdr table accepts insert and cleanup."""
        rc, _, err = _psql(
            "INSERT INTO cdr (call_id, from_user, from_domain, to_user, to_domain, "
            "source_ip, sip_method, call_status, tenant_id) VALUES "
            "('test-call-001', 'devuser', 'dev.tsisip.local', '1000', 'dev.tsisip.local', "
            "'192.0.2.1', 'INVITE', 'completed', '00000000-0000-0000-0000-000000000000')"
        )
        assert rc == 0, f"INSERT failed: {err}"

        rc, out, err = _psql(
            "SELECT COUNT(*) FROM cdr WHERE call_id = 'test-call-001'"
        )
        assert rc == 0, f"SELECT failed: {err}"
        assert out.strip() == "1"

        # Cleanup
        rc, _, err = _psql("DELETE FROM cdr WHERE call_id = 'test-call-001'")
        assert rc == 0, f"DELETE failed: {err}"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
