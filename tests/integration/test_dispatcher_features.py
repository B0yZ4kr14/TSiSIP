"""
Dispatcher Feature Tests (Feature 035)

Validates CRUD, modals, CSV, changelog, rollback features.
"""
import os
import pytest

class TestDispatcherFeatures:
    def test_destination_modal_exists(self):
        """T35.2.4: Destination modal exists"""
        path = 'web/dispatcher.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'modal-overlay' in content, 'Modal overlay missing'
        assert 'modal-title' in content, 'Modal title missing'

    def test_delete_confirmation_exists(self):
        """T35.2.5: Delete confirmation exists"""
        path = 'web/dispatcher.php'
        with open(path) as f:
            content = f.read()
        assert 'doDelete(' in content, 'doDelete function missing'
        assert 'confirm(' in content, 'Confirm dialog missing'

    def test_changelog_section_exists(self):
        """T35.2.6: History tab with changelog exists"""
        path = 'web/dispatcher.php'
        with open(path) as f:
            content = f.read()
        assert 'changelog' in content, 'Changelog section missing'
        assert 'dispatcher_changelog' in content or 'dispatcher_change_log' in content, 'Changelog table missing'

    def test_csv_import_exists(self):
        """T35.2.7: CSV import exists"""
        path = 'web/dispatcher.php'
        with open(path) as f:
            content = f.read()
        assert 'import-form' in content, 'Import form missing'
        assert 'import-csv' in content, 'CSV input missing'

    def test_csv_export_exists(self):
        """T35.2.8: CSV export exists"""
        path = 'web/dispatcher.php'
        with open(path) as f:
            content = f.read()
        assert 'dispatcher-export.php' in content, 'Export link missing'

    def test_reload_button_exists(self):
        """T35.2.2: Apply Changes reload button exists"""
        path = 'web/dispatcher.php'
        with open(path) as f:
            content = f.read()
        assert 'doReload(' in content, 'doReload function missing'
        assert 'dispatcher-reload.php' in content, 'Reload endpoint missing'

    def test_transaction_safety(self):
        """T35.3.5: Transaction safety on CRUD"""
        path = 'web/api/v1/dispatcher-crud.php'
        with open(path) as f:
            content = f.read()
        assert 'beginTransaction' in content, 'beginTransaction missing'
        assert 'rollBack' in content, 'rollBack missing'
        assert 'commit' in content, 'commit missing'

    def test_rate_limit_exists(self):
        """T35.3.2: Rate limit on reload"""
        path = 'web/api/v1/dispatcher-reload.php'
        with open(path) as f:
            content = f.read()
        assert '429' in content, 'HTTP 429 missing'
        assert 'Rate limit exceeded' in content, 'Rate limit message missing'
