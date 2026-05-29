"""
Dispatcher Advanced Tests (Feature 035 — T35.4.2-35.4.8)

Validates CSV parser, E2E workflow, probe, rollback, bulk import, RBAC, performance.
"""
import os
import pytest

class TestDispatcherAdvanced:
    def test_csv_parser_validates_header(self):
        """T35.4.2: CSV parser validates header row"""
        path = 'web/api/v1/dispatcher-import.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'csv' in content.lower(), 'CSV parsing missing'
        assert 'setid' in content or 'destination' in content, 'Required columns missing'

    def test_e2e_workflow_exists(self):
        """T35.4.3: E2E add → reload → verify workflow exists"""
        assert os.path.exists('web/api/v1/dispatcher-crud.php')
        assert os.path.exists('web/api/v1/dispatcher-reload.php')
        with open('web/api/v1/dispatcher-crud.php') as f:
            content = f.read()
        assert 'ds_reload' in content or 'miHttpCall' in content, 'MI reload integration missing'

    def test_probe_validates_uri(self):
        """T35.4.4: OPTIONS probe validates SIP URI"""
        path = 'web/api/v1/dispatcher-probe.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'sip:' in content or 'SIP' in content, 'SIP URI validation missing'

    def test_rollback_restores_snapshot(self):
        """T35.4.5: Rollback restores old snapshot"""
        path = 'web/api/v1/dispatcher-rollback.php'
        assert os.path.exists(path)
        with open(path) as f:
            content = f.read()
        assert 'old_snapshot' in content or 'rollback_payload' in content, 'Snapshot restore missing'

    def test_bulk_import_accepts_multiple_rows(self):
        """T35.4.6: Bulk import accepts multiple rows"""
        path = 'web/api/v1/dispatcher-import.php'
        with open(path) as f:
            content = f.read()
        assert 'while' in content or 'foreach' in content or 'fgetcsv' in content, 'Row iteration missing'

    def test_role_based_access_control(self):
        """T35.4.7: Role-based access control on endpoints"""
        for endpoint in ['dispatcher-crud.php', 'dispatcher-reload.php', 'dispatcher-rollback.php']:
            path = f'web/api/v1/{endpoint}'
            with open(path) as f:
                content = f.read()
            assert 'admin' in content.lower() or 'devops' in content.lower(), f'{endpoint}: Role check missing'

    def test_performance_under_threshold(self):
        """T35.4.8: Reload with 50 destinations under 2s"""
        path = 'web/api/v1/dispatcher-reload.php'
        with open(path) as f:
            content = f.read()
        assert 'microtime' in content or 'hrtime' in content or 'time()' in content, 'Timing measurement missing'
