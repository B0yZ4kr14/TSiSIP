#!/usr/bin/env python3
"""
Feature 035 Integration Tests: Smart Dispatcher Management
Validates: CRUD endpoints, changelog audit, rollback, CSV export/import, frontend page.

Test IDs:
- T35.1: Database migration creates dispatcher_change_log table
- T35.2: dispatcher-crud.php endpoint exists with auth guards and changelog
- T35.3: dispatcher-reload.php endpoint exists with rate limiting
- T35.4: dispatcher-probe.php endpoint exists with SIP OPTIONS probe
- T35.5: dispatcher-rollback.php endpoint exists with snapshot-based rollback
- T35.6: dispatcher-export.php returns CSV with correct headers
- T35.7: dispatcher-import.php accepts CSV with validation
- T35.8: dispatcher.php frontend page exists with CRUD UI
"""

import os
import pytest

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))


class TestDispatcherCrudFiles:
    """T35.2-T35.7: All backend endpoints exist."""

    ENDPOINTS = [
        ("web/api/v1/dispatcher-crud.php", "dispatcher-crud.php"),
        ("web/api/v1/dispatcher-reload.php", "dispatcher-reload.php"),
        ("web/api/v1/dispatcher-probe.php", "dispatcher-probe.php"),
        ("web/api/v1/dispatcher-rollback.php", "dispatcher-rollback.php"),
        ("web/api/v1/dispatcher-export.php", "dispatcher-export.php"),
        ("web/api/v1/dispatcher-import.php", "dispatcher-import.php"),
    ]

    @pytest.mark.parametrize("path, name", ENDPOINTS)
    def test_endpoint_exists(self, path, name):
        full = os.path.join(PROJECT_ROOT, path)
        assert os.path.exists(full), f"{name} endpoint missing"

    def test_crud_has_auth_guard(self):
        """T35.2: dispatcher-crud.php requires authentication."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-crud.php")
        with open(path) as f:
            content = f.read()
        assert "requireAuth()" in content, "dispatcher-crud.php missing auth guard"
        assert "admin" in content or "devops" in content, "dispatcher-crud.php missing role check"

    def test_crud_has_changelog_insert(self):
        """T35.2: dispatcher-crud.php inserts into dispatcher_change_log."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-crud.php")
        with open(path) as f:
            content = f.read()
        assert "dispatcher_change_log" in content, "dispatcher-crud.php missing changelog insert"
        assert "logChange" in content, "dispatcher-crud.php missing logChange function call"

    def test_reload_has_rate_limit(self):
        """T35.3: dispatcher-reload.php has rate limiting."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-reload.php")
        with open(path) as f:
            content = f.read()
        assert "429" in content, "dispatcher-reload.php missing rate limit (429)"
        assert "Rate limit exceeded" in content, "dispatcher-reload.php missing rate limit message"
        assert "ds_reload" in content, "dispatcher-reload.php missing ds_reload MI call"

    def test_probe_has_sip_options(self):
        """T35.4: dispatcher-probe.php sends SIP OPTIONS probe."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-probe.php")
        with open(path) as f:
            content = f.read()
        assert "OPTIONS" in content, "dispatcher-probe.php missing SIP OPTIONS"
        assert "fsockopen" in content, "dispatcher-probe.php missing UDP socket"
        assert "rtt_ms" in content, "dispatcher-probe.php missing RTT measurement"

    def test_rollback_has_snapshot_validation(self):
        """T35.5: dispatcher-rollback.php validates old_snapshot."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-rollback.php")
        with open(path) as f:
            content = f.read()
        assert "old_snapshot" in content, "dispatcher-rollback.php missing old_snapshot check"
        assert "dispatcher_change_log" in content, "dispatcher-rollback.php missing changelog query"
        assert "ROLLBACK" in content, "dispatcher-rollback.php missing ROLLBACK action"

    def test_export_returns_csv(self):
        """T35.6: dispatcher-export.php produces CSV output."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-export.php")
        with open(path) as f:
            content = f.read()
        assert "text/csv" in content, "dispatcher-export.php missing CSV content-type"
        assert "Content-Disposition" in content, "dispatcher-export.php missing download header"
        assert "fputcsv" in content, "dispatcher-export.php missing fputcsv"

    def test_import_validates_csv_header(self):
        """T35.7: dispatcher-import.php validates CSV header."""
        path = os.path.join(PROJECT_ROOT, "web/api/v1/dispatcher-import.php")
        with open(path) as f:
            content = f.read()
        assert "fgetcsv" in content, "dispatcher-import.php missing fgetcsv"
        assert "setid" in content, "dispatcher-import.php missing setid validation"
        assert "sip" in content, "dispatcher-import.php missing destination URI validation"


class TestDispatcherMigration:
    """T35.1: dispatcher_change_log migration exists and has correct schema."""

    def test_migration_file_exists(self):
        path = os.path.join(PROJECT_ROOT, "db/init/04-dispatcher-changelog.sql")
        assert os.path.exists(path), "Migration 04-dispatcher-changelog.sql missing"

    def test_migration_creates_changelog_table(self):
        path = os.path.join(PROJECT_ROOT, "db/init/04-dispatcher-changelog.sql")
        with open(path) as f:
            content = f.read().lower()
        assert "create table" in content, "Migration missing CREATE TABLE"
        assert "dispatcher_change_log" in content, "Migration missing dispatcher_change_log table"
        assert "setid" in content, "Migration missing setid column"
        assert "action" in content, "Migration missing action column"
        assert "old_snapshot" in content, "Migration missing old_snapshot column"
        assert "new_snapshot" in content, "Migration missing new_snapshot column"

    def test_migration_has_index(self):
        path = os.path.join(PROJECT_ROOT, "db/init/04-dispatcher-changelog.sql")
        with open(path) as f:
            content = f.read().lower()
        assert "create index" in content, "Migration missing index"
        assert "setid" in content, "Migration missing setid index"


class TestDispatcherFrontend:
    """T35.8: dispatcher.php frontend page with CRUD UI."""

    def test_page_exists(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        assert os.path.exists(path), "dispatcher.php frontend page missing"

    def test_page_has_crud_table(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "dispatcher-table" in content, "dispatcher.php missing dispatcher table"
        assert "Add Destination" in content or "_('Add Destination')" in content, "dispatcher.php missing add button"

    def test_page_has_changelog_section(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "changelog" in content.lower(), "dispatcher.php missing changelog section"
        assert "Rollback" in content or "_('Rollback')" in content, "dispatcher.php missing rollback button"

    def test_page_has_rollback_modal(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "rollback-overlay" in content, "dispatcher.php missing rollback modal"
        assert "Confirm Rollback" in content or "_('Confirm Rollback')" in content, "dispatcher.php missing rollback confirmation"

    def test_page_has_import_export(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "Export CSV" in content or "_('Export CSV')" in content, "dispatcher.php missing export button"
        assert "Import CSV" in content or "_('Import CSV')" in content, "dispatcher.php missing import button"

    def test_page_has_breadcrumb(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "tsisip-breadcrumb" in content, "dispatcher.php missing breadcrumb"
        assert "dashboard.php" in content, "dispatcher.php missing dashboard breadcrumb link"

    def test_page_has_state_badges(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "tsisip-state-badge" in content, "dispatcher.php missing state badges"
        assert "tsisip-state-badge--active" in content, "dispatcher.php missing active badge"

    def test_page_has_js_handlers(self):
        path = os.path.join(PROJECT_ROOT, "web/dispatcher.php")
        with open(path) as f:
            content = f.read()
        assert "saveDestination" in content, "dispatcher.php missing saveDestination handler"
        assert "doDelete" in content, "dispatcher.php missing doDelete handler"
        assert "doReload" in content, "dispatcher.php missing doReload handler"
        assert "executeRollback" in content, "dispatcher.php missing executeRollback handler"


class TestDispatcherIntegration:
    """Runtime integration tests requiring Docker Compose stack."""

    @pytest.fixture
    def compose_file(self):
        return os.environ.get("COMPOSE_FILE", "docker-compose.yml")

    def test_changelog_table_in_database(self, compose_file):
        """T35.1: dispatcher_change_log table exists in PostgreSQL."""
        import subprocess
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "postgres",
                "psql", "-U", "opensips", "-d", "opensips", "-tAc",
                "SELECT 1 FROM information_schema.tables WHERE table_name = 'dispatcher_change_log';",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("PostgreSQL not reachable")
        assert "1" in r.stdout, "dispatcher_change_log table does not exist"

    def test_dispatcher_endpoint_reachable(self, compose_file):
        """T35.2: dispatcher-crud.php returns 403 for unauthenticated requests."""
        import subprocess
        r = subprocess.run(
            [
                "docker", "compose", "-f", compose_file, "exec", "-T", "ocp",
                "curl", "-s", "-o", "/dev/null", "-w", "%{http_code}",
                "http://localhost/api/v1/dispatcher-crud.php",
            ],
            capture_output=True, text=True,
        )
        if r.returncode != 0:
            pytest.skip("OCP container not reachable")
        assert r.stdout.strip() == "403", f"Expected 403, got {r.stdout.strip()}"


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
