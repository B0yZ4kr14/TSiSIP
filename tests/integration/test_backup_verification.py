"""Feature 032: Automated Backup Verification & DR Testing"""
import subprocess
import glob
import json
import os
import pytest


PROJECT_DIR = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
BACKUP_DIR = os.path.join(PROJECT_DIR, 'backups')


class TestBackupChecksum:
    """T001-T003: Backup generates checksum and metadata"""

    def test_latest_backup_has_checksum(self):
        backups = glob.glob(os.path.join(BACKUP_DIR, 'tsisip_db_*.sql.gz'))
        if not backups:
            pytest.skip('No backups found')
        latest = max(backups, key=os.path.getmtime)
        checksum_file = latest + '.sha256'
        assert os.path.exists(checksum_file), f'Checksum missing for {latest}'

    def test_checksum_is_valid(self):
        backups = glob.glob(os.path.join(BACKUP_DIR, 'tsisip_db_*.sql.gz'))
        if not backups:
            pytest.skip('No backups found')
        latest = max(backups, key=os.path.getmtime)
        checksum_file = latest + '.sha256'
        result = subprocess.run(
            ['sha256sum', '-c', checksum_file],
            capture_output=True, text=True, cwd=PROJECT_DIR
        )
        assert result.returncode == 0, f'Checksum invalid: {result.stderr}'

    def test_backup_has_metadata(self):
        backups = glob.glob(os.path.join(BACKUP_DIR, 'tsisip_db_*.sql.gz'))
        if not backups:
            pytest.skip('No backups found')
        latest = max(backups, key=os.path.getmtime)
        meta_file = latest + '.meta.json'
        assert os.path.exists(meta_file), f'Metadata missing for {latest}'

        with open(meta_file) as f:
            meta = json.load(f)
        assert 'timestamp' in meta
        assert 'size_bytes' in meta
        assert 'checksum' in meta
        assert 'verify_status' in meta


class TestBackupVerificationScript:
    """T004-T005: verify-backup.sh works"""

    def test_verify_script_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'verify-backup.sh')
        assert os.path.exists(script), 'verify-backup.sh not found'
        assert os.access(script, os.X_OK), 'verify-backup.sh not executable'

    def test_verify_latest_backup(self):
        backups = glob.glob(os.path.join(BACKUP_DIR, 'tsisip_db_*.sql.gz'))
        if not backups:
            pytest.skip('No backups found')
        latest = max(backups, key=os.path.getmtime)

        result = subprocess.run(
            ['bash', 'scripts/verify-backup.sh', '--backup', latest],
            capture_output=True, text=True, cwd=PROJECT_DIR, timeout=120
        )
        assert result.returncode == 0, f'Verification failed: {result.stdout}\n{result.stderr}'
        assert 'VERIFICATION PASSED' in result.stdout


class TestBackupDashboard:
    """T007-T008: OCP backup status page"""

    def test_backup_status_page_exists(self):
        page = os.path.join(PROJECT_DIR, 'web', 'backup-status.php')
        assert os.path.exists(page), 'backup-status.php not found'

    def test_backup_status_in_nav(self):
        nav = os.path.join(PROJECT_DIR, 'web', 'common', 'role-nav.php')
        with open(nav) as f:
            content = f.read()
        assert 'backup-status' in content, 'backup-status not in navigation'


class TestBackupMetrics:
    """T009-T010: Prometheus metrics and alerts"""

    def test_backup_metrics_script_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'backup-metrics.sh')
        assert os.path.exists(script), 'backup-metrics.sh not found'

    def test_backup_alert_rules_exist(self):
        rules = os.path.join(PROJECT_DIR, 'docker', 'prometheus', 'alert-rules.yml')
        with open(rules) as f:
            content = f.read()
        assert 'TSiSIPBackupVerifyFailed' in content
        assert 'TSiSIPBackupTooOld' in content


class TestDRDrill:
    """T011-T012: DR drill automation"""

    def test_dr_drill_script_exists(self):
        script = os.path.join(PROJECT_DIR, 'scripts', 'dr-drill.sh')
        assert os.path.exists(script), 'dr-drill.sh not found'
        assert os.access(script, os.X_OK), 'dr-drill.sh not executable'

    def test_dr_drill_cron_config_exists(self):
        cron = os.path.join(PROJECT_DIR, 'deploy', 'ansible', 'dr-drill-cron.yml')
        assert os.path.exists(cron), 'dr-drill-cron.yml not found'
